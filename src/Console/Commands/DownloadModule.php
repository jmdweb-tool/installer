<?php

namespace Jmdweb\Installer\Console\Commands;

use Illuminate\Support\Str;

trait DownloadModule
{

    public function getDownloadLink($licence_key)
    {
        return rtrim(config('app.url'), "/")."/download/".$licence_key."/cms";
        
    }


    public function downloadModuleFromServer($uri)
    {
        $file = $this->download($uri);

        $this->extractFile($file);
    }


    public function download($uri)
    {
        $this->info('Downloading zip file from server...');

        $temp_path = sys_get_temp_dir();

        $file_name_temp = $temp_path . "/main.zip";

        $modules = file_get_contents($uri);

        if ($result = json_decode($modules, true)) {
            if (isset($result['status']) && $result['status'] == "error") {
                throw new \Exception($result['message']);
            }
        }

        $fh = fopen($file_name_temp, 'w');

        fwrite($fh, $modules);

        if (is_file($file_name_temp)) {
            if (!fwrite($fh, $modules)) {
                throw new \Exception(trans('could_not_save'));
            }
        } else {
            throw new \Exception(trans('could_not_download'));
        }

        fclose($fh);

        $this->info('File has been downloaded.');

        return $file_name_temp;

    }


    public function extractFile($file)
    {
        $this->info('Extracting zip file...');

        $zip = new \ZipArchive();

        $response = $zip->open($file);

        if ($response === TRUE) {
            $zip->extractTo(base_path('Jmdweb'));
            $zip->close();
            $this->info('Zip extracted successfully.');
          } else {
            $this->error('Zip extracted successfully.');
          }
    }
}
