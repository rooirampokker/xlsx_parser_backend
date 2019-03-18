<?php
    use PhpOffice\PhpSpreadsheet\IOFactory;

    class xlsxReader {
        public $spreadsheet;
        public $sheetNames;
        public $reader;
    /*
     *
     */
        function __construct($processFile) {
            $this->reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $this->spreadsheet = $this->reader->load($processFile);
        }
    /*
     *
     */
        function getSheetNames() {
           $this->sheetNames = $this->spreadsheet->getSheetNames();
           $sheetsArray = [];
           foreach( $this->sheetNames as $sheets) {
               array_push($sheetsArray, $sheets);
           }
           return ['success' => 'true',
                   'payload' => $sheetsArray];
        }
    /*getSheetByName
     *
     */
        function matchColToRow($sheetName, $dataStarts = 1) {
            $sheetData = $this->spreadsheet->getSheetByName($sheetName)->toArray(null, true, true, false);
            $sheetData = array_splice($sheetData, $dataStarts-1);
            $headings = array_values(array_shift($sheetData));
            $headings = array_map(function($a) { return ['text' => $a,'dataField' => str_replace(' ','_', strtolower($a))];}, $headings);
            $data = [];
            foreach($sheetData as $rowKey => $row) {
                foreach ($headings as $colKey => $col) {
                    $data[$rowKey][$col['dataField']] = $row[$colKey];
                }
            }
            return ['columns' => $headings,
                    'data'    => $data];
        }

        function getColData($sheetName, $col) {
            $colData = [];
            $sheetData = $this->spreadsheet->getSheetByName($sheetName)->toArray(null, true, true, true);
            foreach ($sheetData as $num => $row) {
                if (strlen($row[$col])) {
                    array_push($colData, $row[$col]);
                }
            }
            return $colData;
        }
    }