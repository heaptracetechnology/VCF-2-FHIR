<?php
namespace App\Library\Services;

class VcfTransformer
{
    public $loopLength;

    /**
     * set loop execution length
     * @param  integer $loopLength
     * @return void
     */
    public function setData($loopLength)
    {
        $this->loopLength = $loopLength;
    }

    /**
     *  convert raw vcf data into json
     * @param  array $vcfSalt
     * @return array
     */
    public function getVcfToJson($vcfSalt)
    {
        //remove new line character from the last element
        $arrayIndex = -1;
        for ($x = 0; $x < $this->loopLength; $x++) {
            $arrayIndex = $arrayIndex + 10;
            if (array_key_exists($arrayIndex,$vcfSalt))
            {
                $inserted    = explode("\n", $vcfSalt[$arrayIndex]);
                array_splice($vcfSalt, $arrayIndex, 1, $inserted);
            }
        }
        $patientId = $vcfSalt[9];
        $patientId = trim(preg_replace('/\s+/', ' ', $patientId));
        $mainObj    = array();
        $myObj      = array();
        for ($i = 1; $i < $this->loopLength; $i++) {
            $firstValue = substr($vcfSalt[0], -5);
            if($firstValue != 'CHROM') {
                dd('Parser failed.... failed to extract header.');
            }else {
                $vcfSalt[0] = $firstValue;
                $vcfSalt[9] = $patientId;
            }

            for ($j=0; $j < 10; $j++) {
                $currentIndex = $i * 10;
                if(!empty($vcfSalt[$currentIndex+$j])){
                    $myObj[$vcfSalt[$j]] = $vcfSalt[$currentIndex+$j];
                }  
            }

            if (!preg_match('/[^A-Za-z]/', $myObj['REF']) && !preg_match('/[^A-Za-z]/', $myObj['ALT']) && ($myObj['FILTER'] == 'PASS' || $myObj['FILTER'] == '.') ) {
                array_push($mainObj, $myObj);
            }    
        }
        $JSON = json_encode($mainObj); 
        return $mainObj;
    }

    /**
     *  extract patient id from filename 
     * @param  string $filename
     * @return string
     */
    public function getPatientId($filename)
    {
        $data = explode(".",$filename);
        return $data[0];
    }

    /**
     *  check for valid filename
     * @param  string $filename
     * @return string
     */
    public function testForValidName($filename)
    {
        $count = substr_count($filename,".");
        if($count >= 3) {
            return true;
        }else {
            return false;
        }
    }
    /**
     *  extract gene type from filename 
     * @param  string $filename
     * @return string
     */
    public function getGenomicRef($filename)
    {
        $data = explode(".",$filename);
        return $data[2];
    }

    /**
     *  extract build number from filename 
     * @param  string $filename
     * @return string
     */
    public function getBuild($filename)
    {
        $data = explode(".",$filename);
        return $data[1];
    }
    


}