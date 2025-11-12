<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\ChapterRepositoryInterface;
use Illuminate\Console\Command;

class SyncChaptersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quran:sync-chapters
                            {--chapter= : Sync specific chapter ID (1-114)}
                            {--language=en : Language code for translations}
                            {--info : Also sync chapter info}
                            {--force : Force refresh existing data}';

    /**
     * The console command description.
     */
    protected $description = 'Sync Quran chapters from API to database';

    /**
     * Execute the console command.
     */
    public function handle(ChapterRepositoryInterface $repository): int
    {
        $language = $this->option('language');
        $chapterId = $this->option('chapter');
        $syncInfo = $this->option('info');

        try {
            if ($chapterId) {
                // Sync single chapter
                return $this->syncSingleChapter($repository, (int)$chapterId, $language, $syncInfo);
            }

            // Sync all chapters
            return $this->syncAllChapters($repository, $language, $syncInfo);
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Sync all chapters.
     */
    private function syncAllChapters(
        ChapterRepositoryInterface $repository,
        string $language,
        bool $syncInfo
    ): int {
        $this->info('Starting to sync all chapters from Quran API...');
        $this->info("Language: {$language}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(114);
        $progressBar->start();

        $success = 0;
        $failed = 0;

        for ($i = 1; $i <= 114; $i++) {
            try {
                $chapter = $repository->syncChapterFromApi($i, $language);

                if ($chapter) {
                    $success++;

                    // Optionally sync chapter info
                    if ($syncInfo) {
                        $repository->syncChapterInfoFromApi($i, $language);
                    }
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->warn("Failed to sync chapter {$i}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Sync completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Total', 114],
            ]
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Sync single chapter.
     */
    private function syncSingleChapter(
        ChapterRepositoryInterface $repository,
        int $chapterId,
        string $language,
        bool $syncInfo
    ): int {
        if ($chapterId < 1 || $chapterId > 114) {
            $this->error('Invalid chapter ID. Must be between 1 and 114.');
            return self::FAILURE;
        }

        $this->info("Syncing chapter {$chapterId}...");
        $this->info("Language: {$language}");
        $this->newLine();

        try {
            $chapter = $repository->syncChapterFromApi($chapterId, $language);

            if ($chapter) {
                $this->info("✓ Chapter synced: {$chapter->name_simple}");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Chapter ID', $chapter->chapter_id],
                        ['Name (Simple)', $chapter->name_simple],
                        ['Name (Arabic)', $chapter->name_arabic],
                        ['Revelation Place', ucfirst($chapter->revelation_place)],
                        ['Verses Count', $chapter->verses_count],
                    ]
                );

                // Optionally sync chapter info
                if ($syncInfo) {
                    $this->info('Syncing chapter info...');
                    $repository->syncChapterInfoFromApi($chapterId, $language);
                    $this->info('✓ Chapter info synced');
                }

                return self::SUCCESS;
            }

            $this->error('Chapter not found in API');
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
