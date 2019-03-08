<?php
namespace App\Library\Services;
use App\Library\Services\TableLookup;
class Common
{
    /**
     * generates current date into fhir format 
     * @return date
     */
    public function getFhirDate() 
    {
        $date = new \DateTime();
        $string =  $date->format('Y-m-d H:i:s');
        return date('c', strtotime( $string));   
    }

   /**
     * provides genotype position from the format field from vcf 
     * for phase swap sort algorithm
     * @param  string $format
     * @return integer
     */
    public function getGenoTypePosition($format)
    {
        $formatSegment = explode(":", $format);
        foreach ($formatSegment as $key => $value) {
             if ((string)$value == 'GT') {
                 return $key;
             } 
        }
    }

    /**
     * provides phase set position from the format field from vcf 
     * for phase swap sort algorithm
     *
     * @param  string $format
     * @return integer
     */
    public function getPhaseSetPosition($format)
    {
        $formatSegment = explode(":", $format);
        foreach ($formatSegment as $key => $value) {
             if ((string)$value == 'PS') {
                 return $key;
             } 
        }
    }

    /**
     * calculate phase set by genotype
     * @param  string $gt 
     * @param  string $ps
     * @return string
     */
    public function getPsByGt($gt,$ps)
    {
        $formatSegments = explode(":", $gt);
        foreach ($formatSegments as $key => $value) {
             if ((string)$value == 'GT') {
                 $gtPosition =  $key;
             } 
        }
        $alleles = explode(":", $ps);
        return $alleles[$gtPosition]; 
    }

    /**
     * returns the mutation type Cis or Trans
     *
     * @param  string $gt1
     * @param  string $gt2
     * @return string
     */
    public function getMutation($gt1, $gt2)
    {
        if ($gt1 == $gt2) {
            return 'Cis';
        } else {
            return 'Trans';
        }
    }

    /**
     * provides phaseset value from the given position  
     *
     * @param  string $phaseSet
     * @param  integer $position
     * @return array
     */
    public function getPhaseSetPositionValue($phaseSet, $position)
    {
        $alleles = explode(":", $phaseSet);
        return $alleles[$position]; 
    }

    /**
     * skip records contains pipe character
     * @param  string $phaseSet
     * @param  integer $position
     * @return boolean
     */
    public function checkForSlash($phaseSet, $position)
    {
        $alleles = explode(":",$phaseSet);
        if (strpos( $alleles[$position], "|")) {
            return true;
        } else{
            return false;
        }
    }

    /**
     * skip record if contain dot (.) caracter
     * @param  string $phaseSet
     * @param  integer $position
     * @return boolean
     */
    public function skipHomo($data, $position)
    {
        $alleles = explode(":", $data);
        $genotype = explode("|", $alleles[$position]);
        if ($genotype[1] == '.') {
            $genotype[1] = 0;
        }
        if ($genotype[0] == '.') {
            $genotype[0] = 0;
        }
        if ( $genotype[0] == $genotype[1]) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * getting genotype and phase set position from format column by passing position 
     *
     * @param  integer $index
     * @param  string $value
     * @param  array $array
     * @return array
     */
    public function searchInVarients($index, $value, $array)
    { 
        $key = array_search($value, array_column($array, $index));
        return $array[$key];
    }
    

 
    public function Parse($url)
    {
        $fileContents = simplexml_load_file($url);
        $data = array();
        $data['DiagnosticReport'] = $fileContents;
        file_put_contents('fhir.json', json_encode($data));
 
    }

    /**
     * provides allelic state for the variant
     * @param  string $format
     * @param  integer $position
     * @return string
     */
    public function getAllelicState($format,$position)
    {
		$alleles = explode(":", $format);
		if (count($alleles) == 1) {
			$extractedAlleles = explode("|", $alleles[0]);
		} else {
            if (strpos($alleles[$position], '|') !== false) {
                $extractedAlleles = explode("|", $alleles[$position]);
            } else {
                    $extractedAlleles = explode("/", $alleles[$position]);
            }
		}
		if ( (int) $extractedAlleles[0] == (int) $extractedAlleles[1]) {
			return 'homozygous';
		} else {
			return 'heterozygous';
		}
    }

    /**
     * provides genedetails by using lookup table
     * @param  string $geneId
     * @return array
     */
    public function GeteGeneDetails($geneId)
    {
        $geneTable = TableLookup::gene_table;
        $key = array_search($geneId, array_column($geneTable['GeneTable'], 'ASIC1'));
        return $geneTable['GeneTable'][$key];
    }
}