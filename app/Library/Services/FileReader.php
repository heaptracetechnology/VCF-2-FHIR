<?php
namespace App\Library\Services;

class FileReader
{
    /**
     * getting each vcf field  seaprated by "\t" and returning array for all the fields
     *
     * @param  string $flieUrl
     * @return array
     */
    public function getVcfRawData($flieUrl) {
          $vcfFile = fopen($flieUrl, "r") or die("Unable to open file!");
          $filedata =  fread($vcfFile,filesize($flieUrl));
          $rawVcfData = explode("\t",$filedata);
		  return $rawVcfData;
	}
}