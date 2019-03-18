<?php
class Utilities {
    /*
     * HTML table generator component
     */
    public function outputHeadingsToTable($result, $exclude) {
        $headingArray = $this->getHeadingsFromResults($result);
        $heading = "";
        foreach($headingArray as $headingVal) {
            if (!in_array($headingVal, $exclude))
                $heading .= "<th>$headingVal</th>";
        }

        return $heading;
    }
    /*
     *
     */
    private function convertMySQLOutputToArray($result) {
        $returnArray = [];
        if (is_object($result)) {
            while ($row = $result->fetch_assoc()) {
                $returnArray[] = $row;
            }
            //set pointer back
            mysqli_data_seek($result, 0);
            return $returnArray;
        }
        return $result;
    }
    /*
     * HTML table generator component - will work with associative array or mysql result set
     * Expects $result to either be an associative array or a database return object, eg:
     *
     */
    public function outputContentsToTable($result, $exclude) {
        $contents = "";
        $recordID = 0;
        $counter = 0;
        $rawData = [];
        $headingArray = $this->getHeadingsFromResults($result);
        $contentsArray = $this->convertMySQLOutputToArray($result);

        foreach($contentsArray as $row) {
            $rawData[] = $row;
            $contents .= "<tr>";
            if (($row['id'] != $recordID) && $counter != 0) {
                $contents .= "<tr><td colspan=" . count($headingArray) . " align='center'>-----------------------------------------------------------------------------------------</td></tr>";
            }
            foreach ($headingArray as $headingKey => $headingVal) {
                if (!in_array($headingVal, $exclude)) {
                    $contents .= "<td>$row[$headingVal]</td>";
                }
            }
            $contents .= "</tr>";
            $recordID = $row['id'];
            $counter++;
        }
        return ['html' => $contents, 'rawData' => $rawData];
    }
    /*
     * HTML table generator component
     */
    function getHeadingsFromResults($result) {
        if (!is_array($result)) {
            $row = $result->fetch_assoc();
            mysqli_data_seek($result, 0);
            return array_keys($row);
        } else {
            return array_keys($result[0]);
        }
    }
    /*
    * HTML dropdown generator
    */
    function generateComboBox($selectData, $selectName, $additionalOptions = '') {
        $output = "<select name=$selectName>";
        $output .= "<option value='all'>All</option>";
        $output .= $additionalOptions;
        $selectedOption = isset($_REQUEST[$selectName]) ? $_REQUEST[$selectName] : 0;
        while ($row = $selectData->fetch_assoc()) {
            $selected = $selectedOption == $row['id'] ? 'selected' : '';
            $output .= "<option value='" . $row['id'] . "' $selected>" . $row['name'] . "</option>";
        }
        $output .= "</select>";
        return $output;
    }
    /*
     *
     */
    function debug($query, $result, $description) {
        $stackTrace = debug_backtrace();
        $query = str_replace("\n\r", "<br>", $query);
        print_r("<br><b>Function:</b>" . $stackTrace[2]['function']);
        print_r("<br><b>Description: </b>" . $description);
        print_r("<br><b>Query: </b><br>" . $query);
        print_r("<b>Results: </b><br>");
        while ($row = $result->fetch_assoc()) {
            print_r($row);
            print_r("<br>");
        }
        mysqli_data_seek($result, 0);
        print_r("<hr><br>");
    }
    /*
     *
     */
    function br2nl($string) {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $string);
    }

    /*
     *
     */
    function mapParams() {
        if (isset($argv))
            if (count($argv)) {
                foreach ($argv as $value)
                    $_REQUEST[$value] = 1;
            }
    }

    /*
    *
    */
    function cleanInput($inputValue, $wrapQuote = true)
    {
        $inputValue = filter_var($inputValue, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $inputValue = trim(stripslashes($inputValue));
        return $wrapQuote ? "'" . $inputValue . "'" : $inputValue;
    }
/*
 *
 */
    function getFileToProcess() {
        $inputFileLocation =  "xlsx_files/";
        $fileToProcess = reset($_FILES);
        if (move_uploaded_file($fileToProcess['tmp_name'], $inputFileLocation.$fileToProcess['name'])) {
            $response = ['success' => 'true',
                         'payload' => $inputFileLocation.$fileToProcess['name']];
        } else {
            $response = ['success' => 'false',
                         'payload' => 'File failed to copy'];
        }
        return $response;
    }
}