<?php

namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\Library\Services\FileReader;
use App\Library\Services\VcfTransformer;
use App\Library\Services\PhaseSwapSort;
use App\Library\Services\XmlGenerator;
use Illuminate\Http\Request;

class Vcftofhir extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Vcf To fhir generate controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling uploaded file 
    | and uses services from App\Library\Services to achive vcf to fhir
    | conversation
    |
    */

    /**
     * Convert an uploaded vcf file into fhir.xml .
     *
     * @param  App\Library\Services\FileReader  $filereaderServiceInstance
     * @param  App\Library\Services\VcfTransformer  $vcfTransformerServiceInstance
     * @param  App\Library\Services\PhaseSwapSort  $phaseSwapSortServiceInstance
     * @param  App\Library\Services\XmlGenerator  $xmlGeneratorServiceInstance
     * @return void
     */

    public function handleUpload(Request $request,
                                FileReader $filereaderServiceInstance, 
                                VcfTransformer $vcfTransformerServiceInstance,
                                PhaseSwapSort $phaseSwapSortServiceInstance,
                                XmlGenerator $xmlGeneratorServiceInstance
    ) {
         
        $file = $request->file('fileToUpload');
        if($file) {
            $shortFileName = $file->getClientOriginalName();
            $publicPath = public_path();
            $fileName = "$publicPath/vcf/$shortFileName";
        }else {
            return view('crawler', ['status' => false,'message' => 'Please select a vcf file to continue..']);
        }

        // check for valid filename
        $isFileValid = $vcfTransformerServiceInstance->testForValidName($fileName);
        if(!$isFileValid) {
            return view('crawler', ['status' => false,'message' => 'Filename is invalid. Filename must be formatted as: [patientId].[b36|b37|b38].[HGNC gene symbol].vcf']);
        }

        

        // get raw data from vcf file
        $rawVcfdata = $filereaderServiceInstance->getVcfRawData($fileName);

        // some math for parser
        $arrayLength = sizeof($rawVcfdata);
        $loopLength = round($arrayLength/9);
        
        // get data parsed by passing raw data
        $vcfTransformerServiceInstance->setData($loopLength);
        $parsedData = $vcfTransformerServiceInstance->getVcfToJson($rawVcfdata);

       // patient id for fhir resourse
        $patientId  = $vcfTransformerServiceInstance->getPatientId($shortFileName);
        $geneType  = $vcfTransformerServiceInstance->getGenomicRef($fileName);
        $build = $vcfTransformerServiceInstance->getBuild($fileName);

        // filter list based on filter criteria
        $psList = $phaseSwapSortServiceInstance->getFilterList($parsedData);
        $sortedPsList = $phaseSwapSortServiceInstance->getSortedFilterList($psList);
       
        $sequenceRelationship = $phaseSwapSortServiceInstance->getSequenceRelation($parsedData, $sortedPsList);
        // generate xml file and store into fhir.xml
        $XML_DATA = $xmlGeneratorServiceInstance->transform($parsedData, $patientId, $geneType, $sequenceRelationship, $build);
        
        if($XML_DATA) {
            return view('crawler', $XML_DATA);
        } else {
            return view('crawler', ['status' => false,'message' => 'No record found in vcf file..']);
        }
    }
}