<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\SocialFeeds\Contracts\SocialProvider;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Jobs\RefreshAllFeedsJob;
use MiPress\SocialFeeds\Jobs\RefreshFeedJob;
use MiPress\SocialFeeds\Models\SocialAccount;
use MiPress\SocialFeeds\Models\SocialFeed;
use MiPress\SocialFeeds\Models\SocialPost;
use MiPress\SocialFeeds\Services\SocialFeedManager;

use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(PermissionSeeder::class);
});

// ── Helper ──

function createAccountWithFeed(array $accountOverrides = [], array $feedOverrides = []): array
{
    $user = User::factory()->create();
    $user->assignRole(UserRole::Admin->value);

    $account = SocialAccount::create([
        'platform' => SocialPlatform::Facebook,
        'platform_account_id' => '123456789',
        'name' => 'Test Page',
        'username' => 'testpage',
        'access_token' => 'fake-token',
        'token_expires_at' => now()->addDays(60),
        'connected_by' => $user->id,
        ...$accountOverrides,
    ]);

    $feed = SocialFeed::create([
        'name' => 'Test Feed',
        'social_account_id' => $account->id,
        'feed_type' => 'posts',
        'posts_count' => 5,
        'cache_ttl' => 3600,
        'is_active' => true,
        'created_by' => $user->id,
        ...$feedOverrides,
    ]);

    return [$account, $feed, $user];
}

function fakeFacebookPostsResponse(int $count = 3): array
{
    $posts = [];
    for ($i = 1; $i <= $count; $i++) {
        $posts[] = [
            'id' => "123456789_{$i}",
            'message' => "Post #{$i} content",
            'created_time' => now()->subHours($i)->toIso8601String(),
            'full_picture' => "https://example.com/image_{$i}.jpg",
            'status_type' => 'added_photos',
            'permalink_url' => "https://facebook.com/post/{$i}",
            'from' => ['id' => '123456789', 'name' => 'Test Page', 'picture' => ['data' => ['url' => 'https://example.com/avatar.jpg']]],
            'likes' => ['summary' => ['total_count' => 10 * $i]],
            'comments' => ['summary' => ['total_count' => 2 * $i]],
            'shares' => ['count' => $i],
            'reactions' => ['summary' => ['total_count' => 12 * $i]],
            'attachments' => ['data' => []],
        ];
    }

    return $posts;
}

// ── RefreshFeedJob ──

describe('RefreshFeedJob', function (): void {
    it('dispatches to configured queue', function (): void {
        Queue::fake();

        RefreshFeedJob::dispatch(1);

        Queue::assertPushed(RefreshFeedJob::class, function (RefreshFeedJob $job) {
            return $job->feedId === 1;
        });
    });

    it('skips inactive feed', function (): void {
        [, $feed] = createAccountWithFeed([], ['is_active' => false]);

        Http::fake();

        $job = new RefreshFeedJob($feed->id);
        $job->handle(app(SocialFeedManager::class));

        Http::assertNothingSent();
    });

    it('skips non-existent feed', function (): void {
        Http::fake();

        $job = new RefreshFeedJob(99999);
        $job->handle(app(SocialFeedManager::class));

        Http::assertNothingSent();
    });
});

// ── RefreshAllFeedsJob ──

describe('RefreshAllFeedsJob', function (): void {
    it('dispatches individual RefreshFeedJob for each active feed', function (): void {
        Queue::fake();

        [$account] = createAccountWithFeed();
        SocialFeed::create([
            'name' => 'Second Feed',
            'social_account_id' => $account->id,
            'feed_type' => 'posts',
            'posts_count' => 5,
            'is_active' => true,
            'created_by' => $account->connected_by,
        ]);
        SocialFeed::create([
            'name' => 'Inactive Feed',
            'social_account_id' => $account->id,
            'feed_type' => 'posts',
            'posts_count' => 5,
            'is_active' => false,
            'created_by' => $account->connected_by,
        ]);

        (new RefreshAllFeedsJob)->handle();

        Queue::assertPushed(RefreshFeedJob::class, 2);
    });
});

// ── SocialFeedManager ──

describe('SocialFeedManager', function (): void {
    it('resolves Facebook provider', function (): void {
        $manager = app(SocialFeedManager::class);

        expect($manager->resolve(SocialPlatform::Facebook))
            ->toBeInstanceOf(SocialProvider::class);
    });

    it('throws for unsupported platform', function (): void {
        $manager = app(SocialFeedManager::class);

        expect(fn () => $manager->resolve(SocialPlatform::Instagram))
            ->toThrow(InvalidArgumentException::class);
    });

    it('fetches posts via provider and persists to database', function (): void {
        [$account, $feed] = createAccountWithFeed();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => fakeFacebookPostsResponse(3),
            ]),
        ]);

        $manager = app(SocialFeedManager::class);
        $result = $manager->refreshFeed($feed);

        expect($result)->toHaveCount(3);
        expect(SocialPost::where('social_feed_id', $feed->id)->count())->toBe(3);
    });

    it('caches feed data and returns cached on second call', function (): void {
        [$account, $feed] = createAccountWithFeed();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => fakeFacebookPostsResponse(2),
            ]),
        ]);

        $manager = app(SocialFeedManager::class);

        // First call — fetches from API
        $manager->getFeedData($feed);
        // Second call — should use cache, no additional HTTP calls
        $result = $manager->getFeedData($feed);

        expect($result)->toHaveCount(2);
    });

    it('clears cache on refreshFeed', function (): void {
        [$account, $feed] = createAccountWithFeed();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => fakeFacebookPostsResponse(2),
            ]),
        ]);

        $manager = app(SocialFeedManager::class);

        $firstResult = $manager->getFeedData($feed);
        $secondResult = $manager->refreshFeed($feed);

        expect($firstResult)->toHaveCount(2);
        expect($secondResult)->toHaveCount(2);
    });

    it('returns backup posts from database when token is expired', function (): void {
        [$account, $feed] = createAccountWithFeed([
            'token_expires_at' => now()->subDay(),
        ]);

        // Pre-populate DB posts as backup
        SocialPost::create([
            'social_feed_id' => $feed->id,
            'platform_post_id' => 'backup_1',
            'content' => 'Backup post',
            'posted_at' => now(),
        ]);

        Http::fake();

        $manager = app(SocialFeedManager::class);
        $result = $manager->getFeedData($feed);

        expect($result)->not->toBeEmpty();
    });

    it('returns empty collection for inactive feed', function (): void {
        [, $feed] = createAccountWithFeed([], ['is_active' => false]);

        $manager = app(SocialFeedManager::class);

        expect($manager->getFeedData($feed))->toBeEmpty();
    });
});

// ── SocialPost upsert ──

describe('SocialPost::upsertFromApi', function (): void {
    it('upserts posts and updates existing by platform_post_id', function (): void {
        [, $feed] = createAccountWithFeed();

        $posts = collect([
            [
                'platform_post_id' => 'post_1',
                'post_type' => 'photo',
                'content' => 'Original content',
                'media' => [['type' => 'image', 'url' => 'https://example.com/1.jpg']],
                'engagement' => ['likes' => 5, 'comments' => 2, 'shares' => 1],
                'posted_at' => now(),
            ],
        ]);

        SocialPost::upsertFromApi($feed, $posts);
        expect(SocialPost::where('social_feed_id', $feed->id)->count())->toBe(1);

        // Update the same post
        $updatedPosts = collect([
            [
                'platform_post_id' => 'post_1',
                'post_type' => 'photo',
                'content' => 'Updated content',
                'media' => [['type' => 'image', 'url' => 'https://example.com/1.jpg']],
                'engagement' => ['likes' => 10, 'comments' => 5, 'shares' => 3],
                'posted_at' => now(),
            ],
        ]);

        SocialPost::upsertFromApi($feed, $updatedPosts);

        expect(SocialPost::where('social_feed_id', $feed->id)->count())->toBe(1);
        expect(SocialPost::where('platform_post_id', 'post_1')->first()->content)->toBe('Updated content');
    });

    it('cleans up old posts not present in API response after 7 days', function (): void {
        [, $feed] = createAccountWithFeed();

        // Create an old post that's no longer in the API
        $stalePost = SocialPost::create([
            'social_feed_id' => $feed->id,
            'platform_post_id' => 'stale_post',
            'content' => 'Old content',
            'posted_at' => now()->subDays(10),
        ]);

        // Force updated_at to over 7 days ago (bypassing Eloquent timestamps)
        DB::table('social_posts')
            ->where('id', $stalePost->id)
            ->update(['updated_at' => now()->subDays(8)]);

        // Upsert with new posts only
        SocialPost::upsertFromApi($feed, collect([
            ['platform_post_id' => 'new_post', 'content' => 'New', 'posted_at' => now()],
        ]));

        expect(SocialPost::where('platform_post_id', 'stale_post')->exists())->toBeFalse();
        expect(SocialPost::where('platform_post_id', 'new_post')->exists())->toBeTrue();
    });
});

// ── Filter logic ──

describe('SocialFeedManager filters', function (): void {
    it('filters out posts below min_engagement threshold', function (): void {
        [$account, $feed] = createAccountWithFeed([], [
            'filter_settings' => ['min_engagement' => 15],
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '123_high',
                        'message' => 'Popular post',
                        'created_time' => now()->toIso8601String(),
                        'full_picture' => 'https://example.com/pic.jpg',
                        'status_type' => 'added_photos',
                        'permalink_url' => 'https://fb.com/1',
                        'likes' => ['summary' => ['total_count' => 20]],
                        'comments' => ['summary' => ['total_count' => 5]],
                        'shares' => ['count' => 3],
                        'reactions' => ['summary' => ['total_count' => 25]],
                        'attachments' => ['data' => []],
                    ],
                    [
                        'id' => '123_low',
                        'message' => 'Unpopular post',
                        'created_time' => now()->toIso8601String(),
                        'full_picture' => 'https://example.com/pic2.jpg',
                        'status_type' => 'added_photos',
                        'permalink_url' => 'https://fb.com/2',
                        'likes' => ['summary' => ['total_count' => 1]],
                        'comments' => ['summary' => ['total_count' => 0]],
                        'shares' => ['count' => 0],
                        'reactions' => ['summary' => ['total_count' => 1]],
                        'attachments' => ['data' => []],
                    ],
                ],
            ]),
        ]);

        $manager = app(SocialFeedManager::class);
        $result = $manager->refreshFeed($feed);

        expect($result)->toHaveCount(1);
        expect($result->first()['content'])->toBe('Popular post');
    });

    it('excludes specified post types', function (): void {
        [$account, $feed] = createAccountWithFeed([], [
            'filter_settings' => ['exclude_types' => ['link']],
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '123_photo',
                        'message' => 'Photo post',
                        'created_time' => now()->toIso8601String(),
                        'full_picture' => 'https://example.com/1.jpg',
                        'status_type' => 'added_photos',
                        'permalink_url' => 'https://fb.com/1',
                        'likes' => ['summary' => ['total_count' => 5]],
                        'comments' => ['summary' => ['total_count' => 1]],
                        'shares' => ['count' => 0],
                        'reactions' => ['summary' => ['total_count' => 5]],
                        'attachments' => ['data' => []],
                    ],
                    [
                        'id' => '123_link',
                        'message' => 'Link post',
                        'created_time' => now()->toIso8601String(),
                        'permalink_url' => 'https://fb.com/2',
                        'likes' => ['summary' => ['total_count' => 5]],
                        'comments' => ['summary' => ['total_count' => 1]],
                        'shares' => ['count' => 0],
                        'reactions' => ['summary' => ['total_count' => 5]],
                        'attachments' => ['data' => [['media_type' => 'link']]],
                    ],
                ],
            ]),
        ]);

        $manager = app(SocialFeedManager::class);
        $result = $manager->refreshFeed($feed);

        expect($result)->toHaveCount(1);
        expect($result->first()['content'])->toBe('Photo post');
    });

    it('filters out posts with unavailable attachment text', function (): void {
        [$account, $feed] = createAccountWithFeed([], [
            'filter_settings' => ['hide_unavailable' => true],
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '123_ok',
                        'message' => 'Normal post',
                        'created_time' => now()->toIso8601String(),
                        'full_picture' => 'https://example.com/1.jpg',
                        'status_type' => 'added_photos',
                        'permalink_url' => 'https://fb.com/1',
                        'likes' => ['summary' => ['total_count' => 5]],
                        'comments' => ['summary' => ['total_count' => 1]],
                        'shares' => ['count' => 0],
                        'reactions' => ['summary' => ['total_count' => 5]],
                        'attachments' => ['data' => []],
                    ],
                    [
                        'id' => '123_unavailable',
                        'created_time' => now()->toIso8601String(),
                        'status_type' => 'shared_story',
                        'permalink_url' => 'https://fb.com/2',
                        'likes' => ['summary' => ['total_count' => 5]],
                        'comments' => ['summary' => ['total_count' => 1]],
                        'shares' => ['count' => 0],
                        'reactions' => ['summary' => ['total_count' => 5]],
                        'attachments' => ['data' => [['title' => "Content isn't available right now"]]],
                    ],
                ],
            ]),
        ]);

        $manager = app(SocialFeedManager::class);
        $result = $manager->refreshFeed($feed);

        expect($result)->toHaveCount(1);
        expect($result->first()['content'])->toBe('Normal post');
    });
});

// ── SocialAccount model ──

describe('SocialAccount model', function (): void {
    it('encrypts and decrypts access token', function (): void {
        $user = User::factory()->create();

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'platform_account_id' => '999',
            'name' => 'Token Test',
            'access_token' => 'my-secret-token',
            'connected_by' => $user->id,
        ]);

        $account->refresh();

        // Raw DB value should be encrypted (not plaintext)
        $rawToken = DB::table('social_accounts')->where('id', $account->id)->value('access_token');
        expect($rawToken)->not->toBe('my-secret-token');
        // Decrypted accessor should return original
        expect($account->decrypted_token)->toBe('my-secret-token');
    });

    it('detects expired token', function (): void {
        $user = User::factory()->create();

        $expired = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'platform_account_id' => '111',
            'name' => 'Expired',
            'access_token' => 'test-token',
            'token_expires_at' => now()->subDay(),
            'connected_by' => $user->id,
        ]);

        $valid = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'platform_account_id' => '222',
            'name' => 'Valid',
            'access_token' => 'test-token',
            'token_expires_at' => now()->addDays(30),
            'connected_by' => $user->id,
        ]);

        expect($expired->isTokenExpired())->toBeTrue();
        expect($valid->isTokenExpired())->toBeFalse();
    });

    it('detects token expiring soon', function (): void {
        $user = User::factory()->create();

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'platform_account_id' => '333',
            'name' => 'Expiring Soon',
            'access_token' => 'test-token',
            'token_expires_at' => now()->addDays(3),
            'connected_by' => $user->id,
        ]);

        expect($account->isTokenExpiringSoon(7))->toBeTrue();
        expect($account->isTokenExpiringSoon(2))->toBeFalse();
    });

    it('records and clears errors', function (): void {
        $user = User::factory()->create();

        $account = SocialAccount::create([
            'platform' => SocialPlatform::Facebook,
            'platform_account_id' => '444',
            'name' => 'Error Test',
            'access_token' => 'test-token',
            'connected_by' => $user->id,
        ]);

        $account->recordError('API rate limit exceeded', '32');
        $account->refresh();

        expect($account->errors)->toHaveCount(1);
        expect($account->errors[0]['message'])->toBe('API rate limit exceeded');
        expect($account->errors[0]['code'])->toBe('32');

        $account->clearErrors();
        $account->refresh();

        expect($account->errors)->toBeNull();
    });
});

// ── SocialFeed model ──

describe('SocialFeed model', function (): void {
    it('auto-generates slug on create', function (): void {
        [, $feed] = createAccountWithFeed([], ['name' => 'My Test Feed']);

        expect($feed->slug)->toBe('my-test-feed');
    });

    it('returns correct display settings with defaults', function (): void {
        [, $feed] = createAccountWithFeed([], ['settings' => ['columns' => 4, 'show_author' => false]]);

        expect($feed->displaySetting('columns'))->toBe(4);
        expect($feed->displaySetting('show_author'))->toBeFalse();
        expect($feed->displaySetting('show_engagement'))->toBeTrue();
        expect($feed->displaySetting('per_page'))->toBe(5);
    });

    it('returns correct filter settings with defaults', function (): void {
        [, $feed] = createAccountWithFeed([], ['filter_settings' => ['min_engagement' => 10]]);

        expect($feed->filterSetting('min_engagement'))->toBe(10);
        expect($feed->filterSetting('hide_unavailable'))->toBeTrue();
        expect($feed->filterSetting('exclude_types'))->toBe([]);
    });

    it('has active scope', function (): void {
        createAccountWithFeed([], ['is_active' => true, 'name' => 'Active Feed']);
        createAccountWithFeed(['platform_account_id' => '987654321'], ['is_active' => false, 'name' => 'Inactive Feed']);

        expect(SocialFeed::active()->count())->toBe(1);
        expect(SocialFeed::active()->first()->name)->toBe('Active Feed');
    });
});
