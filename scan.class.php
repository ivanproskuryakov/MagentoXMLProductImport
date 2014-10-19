<?php

class Scan {

    function ScanDirByTime($folder) {
        $dircontent = scandir($folder);
        $arr = array();
        foreach ($dircontent as $filename) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if ($ext == 'xml') {
                if (filemtime($folder . $filename) === false)
                    return false;
                $dat = date("YmdHis", filemtime($folder . $filename));
                $arr[$dat] = $filename;
            }
        }
        if (!ksort($arr))
            return false;
        return $arr;
    }

    function BuildArrayFromXML($XML) {
        $doc = new DOMDocument;
        $xmlReader = new XMLReader();
        if (!$xmlReader->open($XML, null, LIBXML_NOERROR | LIBXML_NOWARNING | 1)) {
            throw exit("Failed to open xml file.");
        }

        $total = 0;
        $offers = array();

        while ($xmlReader->read()) {

            if ($xmlReader->nodeType == XMLReader::ELEMENT) {

                try {
                    if ($xmlReader->localName == 'item') {
                        $node = simplexml_import_dom($doc->importNode($xmlReader->expand(), true));
                        //                    var_dump($node);
                        $id = (string) $node->Code;

                        $offers[$id]['Code'] = trim((string) $node->Code);
                        $offers[$id]['Brand'] = trim((string) $node->Brand);
                        $offers[$id]['Category'] = trim((string) $node->Category);
                        $offers[$id]['CategoryEng'] = trim((string) $node->CategoryEng);
                        
                        $offers[$id]['SegmentSecond'] = trim((string) $node->SegmentSecond);
                        $offers[$id]['CategorySecond'] = trim((string) $node->CategorySecond);
                        
                        $offers[$id]['Segment'] = trim((string) $node->Segment);
                        $offers[$id]['DescrRus'] = trim((string) $node->DescrRus);
                        $offers[$id]['DescrEng'] = trim((string) $node->DescrEng);
                        $offers[$id]['Season'] = trim((string) $node->Season);

                        $offers[$id]['NameEng'] = trim((string) $node->NameEng);
                        $offers[$id]['NameRus'] = trim((string) $node->NameRus);
                        $offers[$id]['CompEng'] = trim((string) $node->CompEng);
                        $offers[$id]['CompRus'] = trim((string) $node->CompRus);
                        $offers[$id]['BrCountryRus'] = trim((string) $node->BrCountryRus);
                        $offers[$id]['ProdRegEng'] = trim((string) $node->ProdRegEng);
                        $offers[$id]['ProdRegRus'] = trim((string) $node->ProdRegRus);
                        $offers[$id]['CareEng'] = trim((string) $node->CareEng);
                        $offers[$id]['CareRus'] = trim((string) $node->CareRus);
                        $offers[$id]['new_product'] = trim((string) $node->new_product);

                        foreach ($node->Sizes->Size as $_size) {
                            $size = array();
                            $size['id'] = trim((string) $_size->ID);
                            $size['Barcode'] = trim((string) $_size->Barcode);
                            $size['Remains'] = trim((string) $_size->Remains);
                            $size['Price']   = trim((string) $_size->Price);
                            $size['DiscountPrice']   = trim((string) $_size->DiscountPrice);
                            $size['SizeEng'] = trim((string) $_size->SizeEng);
                            $size['SizeRus'] = trim((string) $_size->SizeRus);
                            $offers[$id]['size'][] = $size;
                        }
                        $offers[$id]['ColorEng'] = trim((string)$node->ColorEng);                        
                        $offers[$id]['color'] = trim((string)$node->ColorRus->HTMLcode);
                        $offers[$id]['ColorRus'] = trim((string)$node->ColorRus->Rus);          

                        
                        if ($node->Images->img) {
                            foreach ($node->Images->img as $_img) {
                                if (strlen($_img)>0) $offers[$id]['img'][] = (string) $_img;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    echo 'Product with Code# ' . $id . ' not valid<br/>';
                }
            }
        }
        foreach ($offers as $_offer) {
            $total++;
        }
        return $offers;
    }

}

?>