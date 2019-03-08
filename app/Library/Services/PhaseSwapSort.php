<?php
namespace App\Library\Services;
  
class PhaseSwapSort extends Common 
{
    /**
     * provides filtered list based on phase set 
     * @param array $list
     * @return array
     */
    public function getFilterList($list)
    {
        $psList = array();
        foreach ($list as $key => $value) {
            $lastElement = end($value);
            $psPosition =  $this->getPhaseSetPosition($value['FORMAT']);
            $gtPosition =  $this->getGenoTypePosition($value['FORMAT']);
            $psValue = $this->getPhaseSetPositionValue($lastElement,$psPosition);
            $gtValue = $this->checkForSlash($lastElement,$gtPosition);
            if($gtValue) {
                $validGt = $this->skipHomo($lastElement,$gtPosition);
            }
            if(is_numeric($psValue) && $gtValue && $validGt) {
                 $headerArray = array_keys($value);
                 $arrayKey = $headerArray[9];  
                $value[$arrayKey] = $psValue;
                array_push($psList,$value);
            }
        }
        return $psList;
    }
    
    /**
     * sort filtered list returned by getFilterList() in ascending order
     * @param array $list
     * @return array
     */
    public function getSortedFilterList($list)
    {
        $sortedFilterList = array();
        foreach($list as $key => $item) {
            $headerArray = array_keys($item);
            $arrayKey = $headerArray[9];  
            
            $sortedFilterList[$item[$arrayKey]][$key] = $item;
        }
        ksort($sortedFilterList, SORT_NUMERIC);
        return $sortedFilterList;    
    }

    /**
     * get sequence relationship based on phase set
     * @param array $parsedData
     * @param array $SORTED_PS_LIST
     * @return array
     */
    public function getSequenceRelation($parsedData,$sortedPsList)
    {
        $relationship = array();
        foreach ($sortedPsList as $key => $value1) {
        
           $value1 = array_values($value1);
           foreach ($value1 as $key => $value) {
            $arrayLength =   count($value1);
            if($key < $arrayLength-1) {
                $position1 = $value1[$key];
                $position2 = $value1[$key+1];
               
                $headerArray = array_keys($value);
 
                $varientWithPosition1 = $this->searchInVarients('POS',$position1['POS'],$parsedData);
                $varientWithPosition2 = $this->searchInVarients('POS',$position2['POS'],$parsedData);
               
                end( $varientWithPosition1 );
                end( $varientWithPosition2 );
                $key1 = key( $varientWithPosition1 );
                $key2 = key( $varientWithPosition2 );
                $mt1 =  $this->getPsByGt($varientWithPosition1['FORMAT'],$varientWithPosition1[$key1]);
                $mt2 =  $this->getPsByGt($varientWithPosition2['FORMAT'],$varientWithPosition2[$key2]);
     
                $tempArray = array();
                array_push($tempArray,$this->getMutation($mt1,$mt2));
                array_push($tempArray,$varientWithPosition1['POS']);
                array_push($tempArray,$varientWithPosition2['POS']);
                array_push($relationship,$tempArray);
            }
            
           }

        }
        return $relationship;
 
    }
    
}