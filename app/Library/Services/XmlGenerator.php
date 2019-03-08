<?php
namespace App\Library\Services;
 
class XmlGenerator extends Common
{

	/**
     * converts varient array into fhir resource
     * @param  string $filename
     * @return string
     */
	function transform($data,$patientId, $geneType,$sequenceRelationship,$build) {
		$lookupResult = [];
		$lookupResult['status'] = true;
		$lookupResult['message'] = false;
		$geneType = strtoupper($geneType);
		$build = strtolower($build);
		$gene_details = $this->GeteGeneDetails($geneType);
	 	if(!$data) {
			return false;
		}
		if($gene_details['ASIC1'] != $geneType) {
			$lookupResult['isInvalidValidGenetype'] = "$geneType  gene type is not supported ";
			$GENE_ID = 'null';
			$GENE_VALUE = 'null';
			$geneType = 'null';
		}else {
			if($build  == 'b36') {
				$GENE_ID = $gene_details['b36_refSeq'];
			
			}else if($build  == 'b37') {
				$GENE_ID = $gene_details['b37_refSeq'];
			}else if($build  == 'b38') {
				$GENE_ID = $gene_details['b38_refSeq'];
			}else {
				$lookupResult['isInvalidValidBuild'] = true;
				$GENE_ID = 'null';
				$GENE_VALUE = 'null';
			}
			$GENE_VALUE = $gene_details['hgnc_id'];
		}


		$RegionStudied_id = 'rs-'.uniqid();
		if(count($data) == 0) {
			$RegionStudied = 'Absent';
			$RegionCode = 'LA9634-4';
		}
		else {
			$RegionStudied = 'Present';
			$RegionCode = 'LA9633-4';
		}
		
	
		$variation_ref = $data[0]['REF'];
		$variation_alt = $data[0]['ALT'];
		$variation_pos = $data[0]['POS'];
		
		
		$date = date('m-d-Y h:i:s a', time());
		
		$dom = new \DOMDocument();
		
		$dom->encoding = 'utf-8';
		
		$dom->xmlVersion = '1.0';
		
		$dom->formatOutput = true;
		
		$xml_file_name = 'fhir.xml';
		
		$fhir_date_today = $this->getFhirDate();
		
		$entry_node = $dom->createElement('contained');
		
		$bundle = $dom->createElement('DiagnosticReport');
		
		$bundle ->setAttributeNode( new \DOMAttr('xmlns','http://hl7.org/fhir'));
		
		// DiagnosticReport
		$dr_report = $dom->createElement('DiagnosticReport');
		
		$bundle->appendChild($dom->createElement('id'))
		        ->setAttributeNode( new \DOMAttr('value', 'dr-'.uniqid()));
		
		$bundle->appendChild($dom->createElement('meta'))
		                  ->appendChild($dom->createElement('profile'))
		                  ->setAttributeNode( new \DOMAttr('value', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/diagnosticreport'));
		
		$bundle->appendChild($entry_node);
		
		
		// rs observation 
		$dVarients = array();
		
		foreach ($data as $key=>$vcf) {
			
			$varient_and_position = array();
			$variation_id = uniqid();
			array_push($varient_and_position,$vcf['POS']);
			array_push($varient_and_position,$variation_id);
			array_push( $dVarients , $varient_and_position );
			$variation_uid = $dVarients[$key][1];
			$variation_ref = $vcf['REF'];
			$variation_alt = $vcf['ALT'];
			$variation_pos = $vcf['POS'];
			$variation_format = $data[0]['FORMAT'];
			$alleles_position = $this->getGenoTypePosition($variation_format);
			$Alleles = $this->getAllelicState(end($vcf),$alleles_position);
			if($Alleles == 'homozygous') {
				$alleles_code = 'LA6705-3';
			}
			else {
				$alleles_code = 'LA6706-1';
			}
			
			$en2_resourse = $dom->createElement('contained');
			$en2_resourse_observation = $en2_resourse->appendChild($dom->createElement('Observation'));
			$en2_resourse_observation->appendChild($dom->createElement('id'))->setAttributeNode( new \DOMAttr('value', 'dv-'.$variation_uid) );
			$en2_resourse_observation->appendChild($dom->createElement('meta'))->appendChild($dom->createElement('profile'))->setAttributeNode( new \DOMAttr('value', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/obs-described-variant'));
			$en2_resourse_observation->appendChild($dom->createElement('status'))->setAttributeNode( new \DOMAttr('value', 'final') ) ;
			$entry2_category_coding = $en2_resourse_observation->appendChild($dom->createElement('category'))->appendChild($dom->createElement('coding'));
			$entry2_category_coding->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://terminology.hl7.org/CodeSystem/observation-category'));
			$entry2_category_coding->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'laboratory') ) ;
			$entry2_category_code = $en2_resourse_observation->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$entry2_category_code->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') ) ;
			$entry2_category_code->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'TBD-Described') ) ;
			$entry2_category_code->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genetic variant assessment') ) ;
			$en2_resourse_observation->appendChild($dom->createElement('subject'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', 'Patient/'.$patientId));
			$en2_resourse_observation->appendChild($dom->createElement('issued'))->setAttributeNode( new \DOMAttr('value',$fhir_date_today) );
			$entry2_category_codeconcept = $en2_resourse_observation->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$entry2_category_codeconcept->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$entry2_category_codeconcept->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', $RegionCode) ) ;
			$entry2_category_codeconcept->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', $RegionStudied) ) ;
			//$en2_resourse_observation->appendChild($dom->createElement('specimen'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', 'Specimen/specimen1'));
			$en2component = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding = $en2component ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '62374-4') ) ;
			$en2_component_coding->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Human reference sequence assembly version'));
			$en2_componentvalconcept = $en2component->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$en2_componentvalconcept->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_componentvalconcept->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'LA14029-5'));
			$en2_componentvalconcept->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'GRCh37'));
			
			//component 2
			$en2component2 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding2 = $en2component2 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding2->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding2->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '48013-7') ) ;
			$en2_component_coding2->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genomic reference sequence ID'));
			$en2_componentvalconcept2 = $en2component2->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$en2_componentvalconcept2->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://www.ncbi.nlm.nih.gov/nuccore'));
			$en2_componentvalconcept2->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', $GENE_ID));
			
			//component 3
			$en2component3 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding3 = $en2component3 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding3->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding3->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '53034-5') ) ;
			$en2_component_coding3->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Allelic state'));
			$en2_componentvalconcept3 = $en2component3->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$en2_componentvalconcept3->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_componentvalconcept3->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', $alleles_code));
			$en2_componentvalconcept3->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', $Alleles));
			
			//component 4
			$en2component4 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding4 = $en2component4 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding4->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding4->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '69547-8') ) ;
			$en2_component_coding4->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genomic Ref allele'));
			
			$en2component4 ->appendChild($dom->createElement('valueString'))->setAttributeNode( new \DOMAttr('value', $variation_ref));
			
			//component 5
			$en2component5 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding5 = $en2component5 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding5->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding5->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '69551-0') ) ;
			$en2_component_coding5->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genomic Alt allele'));
			$en2component5 ->appendChild($dom->createElement('valueString'))->setAttributeNode( new \DOMAttr('value', $variation_alt));
			
			//component 6
			$en2component6 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding6 = $en2component6 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding6->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding6->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'tbd-coordinate system') ) ;
			$en2_component_coding6->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genomic coordinate system'));
			$en2_component_coding6 = $en2component6->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$en2_component_coding6->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://hl7.org/fhir/uv/genomics-reporting/CodeSystem/genetic-coordinate-system'));
			$en2_component_coding6->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '1'));

			//component 7
			$en2component7 = $en2_resourse_observation->appendChild($dom->createElement('component'));
			$en2_component_coding7 = $en2component7 ->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$en2_component_coding7->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
			$en2_component_coding7->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '81254-5') ) ;
			$en2_component_coding7->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Genomic Allele start-end'));
			$en2_component_coding7 = $en2component7->appendChild($dom->createElement('valueRange'))->appendChild($dom->createElement('low'));
			$en2_component_coding7->appendChild($dom->createElement('value'))->setAttributeNode( new \DOMAttr('value', $variation_pos));
			$bundle->appendChild($en2_resourse);
		}
		$varient_sequence = array();
		foreach ($sequenceRelationship as $key => $value) {
			$sequence_id = 'sid-'.uniqid();
			array_push( $varient_sequence , $sequence_id );	 
		}

		// sequence relationship 
		foreach ($sequenceRelationship as $key => $value) {

			$dv_ref1 = $this->searchInVarients('0',$value[1],$dVarients)[1];
			$dv_ref2 = $this->searchInVarients('0',$value[2],$dVarients)[1];
			$sp_observation = $dom->createElement('contained');
            $main_sp_observation = $sp_observation->appendChild($dom->createElement('Observation'));
			$main_sp_observation->appendChild($dom->createElement('id'))->setAttributeNode( new \DOMAttr('value', $varient_sequence[$key]) );
			
			$main_sp_observation->appendChild($dom->createElement('meta'))->appendChild($dom->createElement('profile'))->setAttributeNode( new \DOMAttr('value', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/obs-sequence-phase-reltn') );
			
			$sp_extension1 = $main_sp_observation->appendChild($dom->createElement('extension'));
			$sp_extension1->setAttributeNode( new \DOMAttr('url', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/obs-focus') );
			$sp_extension1 ->appendChild($dom->createElement('valueReference'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', '#dv-'.$dv_ref1) );
			$sp_extension2 = $main_sp_observation->appendChild($dom->createElement('extension'));
			$sp_extension2->setAttributeNode( new \DOMAttr('url', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/obs-focus') );
			$sp_extension2 ->appendChild($dom->createElement('valueReference'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', '#dv-'.$dv_ref2) );
			$main_sp_observation->appendChild($dom->createElement('status'))->setAttributeNode( new \DOMAttr('value', 'final') );
			$sp_category_coding = $main_sp_observation->appendChild($dom->createElement('category'))->appendChild($dom->createElement('coding'));
			$sp_category_coding->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://terminology.hl7.org/CodeSystem/observation-category') );
			$sp_category_coding->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'laboratory') );
			$sp_category_code = $main_sp_observation->appendChild($dom->createElement('code'))->appendChild($dom->createElement('coding'));
			$sp_category_code->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') );
			$sp_category_code->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'TBD-sequence-phase-relation') );
			$sp_category_code->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', 'Sequence phase relationship') );
			$main_sp_observation->appendChild($dom->createElement('subject'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', 'Patient/'.$patientId) );
			$main_sp_observation->appendChild($dom->createElement('issued'))->setAttributeNode( new \DOMAttr('value', $this->getFhirDate()) );
			$sp_value_code_concept = $main_sp_observation->appendChild($dom->createElement('valueCodeableConcept'))->appendChild($dom->createElement('coding'));
			$sp_value_code_concept->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') );
			$sp_value_code_concept->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', 'TBD-cisTrans') );
			$sp_value_code_concept->appendChild($dom->createElement('display'))->setAttributeNode( new \DOMAttr('value', $value[0]) );
			$bundle->appendChild($sp_observation);
		}

		$bundle->appendChild($dom->createElement('status'))->setAttributeNode( new \DOMAttr('value', 'final'));
		$dr_report_code = $bundle ->appendChild($dom->createElement('code'));
		$dr_report_coding = $dr_report_code->appendChild($dom->createElement('coding'));
		$dr_report_coding->appendChild($dom->createElement('system'))->setAttributeNode( new \DOMAttr('value', 'http://loinc.org'));
		$dr_report_coding->appendChild($dom->createElement('code'))->setAttributeNode( new \DOMAttr('value', '51969-4') ) ;
		$dr_report_code->appendChild($dom->createElement('text'))
		        ->setAttributeNode( new \DOMAttr('value', 'Genetic analysis report'));
		$bundle->appendChild($dom->createElement('subject'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', 'Patient/'.$patientId));
		$bundle->appendChild($dom->createElement('result'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', '#'.$RegionStudied_id));
		foreach ($data as $key => $value) {
			$bundle->appendChild($dom->createElement('result'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', '#dv-'.$dVarients[$key][1]));
		}
		foreach ($varient_sequence as $key => $value) {
			$sequence_id = $value;
 
			$bundle->appendChild($dom->createElement('result'))->appendChild($dom->createElement('reference'))->setAttributeNode( new \DOMAttr('value', '#'.$sequence_id));
		}
	
		//$patient_entry
		$root = $dom->createElement('contained');

		$observation_node = $dom->createElement('Observation');

		$attr_id = new \DOMAttr('value', $RegionStudied_id);

		$child_node_id = $dom->createElement('id');
		
		$child_node_id->setAttributeNode($attr_id);
		
		$child_node_meta = $dom->createElement('meta');
		
		$observation_node->appendChild($child_node_id);
		
		$observation_node->appendChild($child_node_meta);
		
		$child_node_profile = $dom->createElement('profile');
		
		$child_node_meta->appendChild($child_node_profile);
		
		$child_node_profile->setAttributeNode( new \DOMAttr('value', 'http://hl7.org/fhir/uv/genomics-reporting/StructureDefinition/obs-region-studied') );
		
		$child_node_status = $dom->createElement('status');
		
		$child_node_status->setAttributeNode( new \DOMAttr('value', 'final') );
		
		$observation_node->appendChild($child_node_status);
		
		$child_node_category = $dom->createElement('category');
		
		$observation_node->appendChild($child_node_category);
		
		//code
		$child_node_observation_code = $dom->createElement('code');
		
		$observation_node->appendChild($child_node_observation_code);
		
		$child_node_obv_coding = $dom->createElement('coding');
		
		$child_node_observation_code->appendChild($child_node_obv_coding);
		
		$child_node_code_system = $dom->createElement('system');
		
		$child_node_code_system->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') );
		
		$child_node_code_code = $dom->createElement('code');
		
		$child_node_code_code->setAttributeNode( new \DOMAttr('value', 'TBD-RegionsStudied') );
		
		$child_node_code_display = $dom->createElement('display');
		
		$child_node_code_display->setAttributeNode( new \DOMAttr('value', 'Region studied') );
		
		$child_node_obv_coding->appendChild($child_node_code_system);
		
		$child_node_obv_coding->appendChild($child_node_code_code);
		
		$child_node_obv_coding->appendChild($child_node_code_display);

		//subject 
		$child_node_observation_subject = $dom->createElement('subject');
		
		$observation_node->appendChild($child_node_observation_subject);
		
		$child_node_subject_ref = $dom->createElement('reference');
		
		$child_node_observation_subject->appendChild($child_node_subject_ref);
		
		$child_node_subject_ref->setAttributeNode( new \DOMAttr('value', 'Patient/'.$patientId) );
		
		//issued 
		$child_node_observation_subject = $dom->createElement('issued');
		
		$observation_node->appendChild($child_node_observation_subject);
		
		$child_node_observation_subject->setAttributeNode( new \DOMAttr('value', $fhir_date_today) );
		
		//valueCodeableConcept 
		$child_node_observation_valueCodeableConcept = $dom->createElement('valueCodeableConcept');
		
		$observation_node->appendChild($child_node_observation_valueCodeableConcept);
		
		$child_node_obv_concept_coding = $dom->createElement('coding');
		
		$child_node_observation_valueCodeableConcept->appendChild($child_node_obv_concept_coding);
		
		$child_node_code_cdconcept_system = $dom->createElement('system');
		
		$child_node_code_cdconcept_system->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') );
		
		$child_node_code_cdconcept_code = $dom->createElement('code');
		
		$child_node_code_cdconcept_code->setAttributeNode( new \DOMAttr('value', $RegionCode) );
		
		$child_node_code_cdconcept_display = $dom->createElement('display');
		
		$child_node_code_cdconcept_display->setAttributeNode( new \DOMAttr('value', $RegionStudied) );
		
		$child_node_obv_concept_coding->appendChild($child_node_code_cdconcept_system);
		
		$child_node_obv_concept_coding->appendChild($child_node_code_cdconcept_code);
		
		$child_node_obv_concept_coding->appendChild($child_node_code_cdconcept_display);
		
		//category 
		$child_node_coding = $dom->createElement('coding');
		
		$child_node_system = $dom->createElement('system');
		
		$child_node_system->setAttributeNode( new \DOMAttr('value', 'http://terminology.hl7.org/CodeSystem/observation-category') );
		
		$child_node_code = $dom->createElement('code');
		
		$child_node_code->setAttributeNode( new \DOMAttr('value', 'laboratory') );
		
		$child_node_coding->appendChild($child_node_system);
		
		$child_node_coding->appendChild($child_node_code);
		
		$child_node_category->appendChild($child_node_coding);
		
		
		//component 
		$child_node_observation_component = $dom->createElement('component');
		
		$observation_node->appendChild($child_node_observation_component);
		
		//code inside component
		$child_node_observation_component_code = $dom->createElement('code');
		
		$child_node_observation_component->appendChild($child_node_observation_component_code);
		
		$child_node_obv_component_coding = $dom->createElement('coding');
		
		$child_node_observation_component_code->appendChild($child_node_obv_component_coding);
		
		$child_node_code_component_system = $dom->createElement('system');
		
		$child_node_code_component_system->setAttributeNode( new \DOMAttr('value', 'http://loinc.org') );
		
		$child_node_code_component_code = $dom->createElement('code');
		
		$child_node_code_component_code->setAttributeNode( new \DOMAttr('value', '48018-6') );
		
		$child_node_code_component_display = $dom->createElement('display');
		
		$child_node_code_component_display->setAttributeNode( new \DOMAttr('value', 'Gene studied ID') );
		
		$child_node_obv_component_coding->appendChild($child_node_code_component_system);
		
		$child_node_obv_component_coding->appendChild($child_node_code_component_code);
		
		$child_node_obv_component_coding->appendChild($child_node_code_component_display);
		
		
		//valueCodeableConcept inside component
		$child_node_observation_component_valueCodeableConcept = $dom->createElement('valueCodeableConcept');
		
		$child_node_observation_component->appendChild($child_node_observation_component_valueCodeableConcept);
		
		$child_node_obv_component_concept_coding = $dom->createElement('coding');
		
		$child_node_observation_component_valueCodeableConcept->appendChild($child_node_obv_component_concept_coding);
		
		$child_node_code_component_cdconcept_system = $dom->createElement('system');
		
		$child_node_code_component_cdconcept_system->setAttributeNode( new \DOMAttr('value', 'http://www.genenames.org') );
		
		$child_node_code_cdconcept_component_code = $dom->createElement('code');
		
		$child_node_code_cdconcept_component_code->setAttributeNode( new \DOMAttr('value', $GENE_VALUE) );
		
		$child_node_code_cdconcept_component_display = $dom->createElement('display');
		
		$child_node_code_cdconcept_component_display->setAttributeNode( new \DOMAttr('value', $geneType) );
		
		$child_node_obv_component_concept_coding->appendChild($child_node_code_component_cdconcept_system);
		
		$child_node_obv_component_concept_coding->appendChild($child_node_code_cdconcept_component_code);
		
		$child_node_obv_component_concept_coding->appendChild($child_node_code_cdconcept_component_display);
		
		$entry_node->appendChild($observation_node);
		
		$dom->appendChild($bundle);
		
		$dom->save($xml_file_name);
		
		return $lookupResult;
	}
}
