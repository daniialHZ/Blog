<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Update posts that are scheduled and should be published
        $updated = Post::where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        if ($updated) {
            $this->info('Scheduled posts published successfully.');
        } else {
            $this->info('No scheduled posts to publish.');
        }
    }
}
