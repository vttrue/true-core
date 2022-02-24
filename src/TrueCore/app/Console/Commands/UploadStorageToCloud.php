<?php

namespace TrueCore\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Class UploadStorageToCloud
 *
 * @package App\Console\Commands
 */
class UploadStorageToCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:upload-storage {--disk=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simply uploads storage disks to the S3 Cloud storage';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $disk = $this->option('disk');

        if ($disk !== null && (is_string($disk) === false || trim($disk, "/ \t\n\r\0\x0B") === '')) {
            return 0;
        }

        $dirScanner = function (string $dir, ?\Closure $beforeCallback = null) use(&$dirScanner) {

            if (is_dir($dir) === true) {

                $objectList = scandir($dir);

                foreach ($objectList AS $object) {

                    if ($beforeCallback !== null && $beforeCallback($dir, $object) === false) {
                        continue;
                    }

                    if (in_array($object, ['.', '..'], true) === true) {
                        continue;
                    } elseif (is_file($dir . '/' . $object) === true) {
                        yield $dir . '/' . $object;
                    } else {
                        yield from $dirScanner($dir . '/' . $object, $beforeCallback);
                    }
                }
            }
        };

        $storageDir = storage_path('app/public' . ((is_string($disk) === true) ? '/' . trim($disk, "/ \t\n\r\0\x0B") : ''));

        $callback = null;

        foreach ($dirScanner($storageDir, $callback) AS $file) {

            $uploadingResponse = $this->uploadFile($file);

            if ($uploadingResponse['success'] === false) {
                dump($uploadingResponse);
            } else {
                echo $file . ' has been uploaded ' . "\n";
            }
        }

        return 0;
    }

    /**
     * @param string $path
     * @param string $disk
     *
     * @return array
     */
    protected function uploadFile(string $path) : array
    {
        if (file_exists($path) === true) {

            $file = fopen($path, 'r');

            if (is_resource($file) === true) {

                $targetDir = substr($path, strlen(storage_path('app/public')));

                if (Storage::disk('s3')->put($targetDir, $file) === false) {

                    fclose($file);

                    return [
                        'success'   => false,
                        'error'     => 'Unable to save file ' . $path
                    ];

                } else {

                    fclose($file);

                    return [
                        'success'   => true
                    ];

                }

            } else {
                return [
                    'success'   => false,
                    'error'     => 'Unable to open file ' . $path
                ];
            }
        }

        return [
            'success'   => false,
            'error'     => $path . ' dir does not exist'
        ];
    }
}
