<?php

namespace App\Console\Commands;

use App\Models\DictionaryWord;
use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ImportDictionaryWordsCommand extends Command
{
    protected $signature = 'dictionary:import';

    protected $description = 'Command description';

    /**
     * @throws \Throwable
     */
    public function handle()
    {
        $filePath = storage_path('app/words_alpha.txt');

        if (! file_exists($filePath)) {
            $this->error("File not found: $filePath");

            return CommandAlias::FAILURE;
        }

        $this->info('Starting import of dictionary words...');

        // Open file
        $file = fopen($filePath, 'r');

        $batchSize = 1000;
        $batch = [];
        $totalImported = 0;
        $totalSkipped = 0;

        DB::beginTransaction();

        try {
            // Read file line by line
            while (($word = fgets($file)) !== false) {
                $word = trim($word);

                // Skip words less than 3 characters
                if (strlen($word) < 3) {
                    $totalSkipped++;

                    continue;
                }

                $batch[] = ['word' => $word];

                // Insert in batches for better performance
                if (count($batch) >= $batchSize) {
                    DictionaryWord::insert($batch);
                    $totalImported += count($batch);
                    $batch = [];

                    $this->info("Imported $totalImported words so far...");
                }
            }

            // Insert any remaining words
            if (! empty($batch)) {
                DictionaryWord::insert($batch);
                $totalImported += count($batch);
            }

            DB::commit();
            fclose($file);

            $this->info("Import complete! Imported $totalImported words. Skipped $totalSkipped words shorter than 3 characters.");

            return CommandAlias::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error importing words: '.$e->getMessage());

            return CommandAlias::FAILURE;
        }
    }
}
