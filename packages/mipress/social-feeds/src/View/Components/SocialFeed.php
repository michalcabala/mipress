<?php

namespace MiPress\SocialFeeds\View\Components;

use Illuminate\View\Component;
use MiPress\SocialFeeds\Models\SocialFeed as SocialFeedModel;
use MiPress\SocialFeeds\Services\SocialFeedManager;

class SocialFeed extends Component
{
    public SocialFeedModel $feed;

    public $posts;

    public function __construct(
        ?int $id = null,
        ?string $slug = null,
    ) {
        if ($id) {
            $this->feed = SocialFeedModel::findOrFail($id);
        } elseif ($slug) {
            $this->feed = SocialFeedModel::where('slug', $slug)->firstOrFail();
        } else {
            throw new \InvalidArgumentException('Komponenta social-feed vyžaduje atribut id nebo slug.');
        }

        $manager = app(SocialFeedManager::class);
        $this->posts = $manager->getFeedData($this->feed);
    }

    public function render()
    {
        return view('social-feeds::components.feed');
    }
}
