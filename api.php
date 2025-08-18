<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Expose-Headers: x-total-count');
header('Content-Type: application/json; charset=utf-8');
require 'conn.php';
require_once 'C:\wamp\www\KaizenCloud\PHPExcel\Classes\PHPExcel.php';
include 'C:\wamp\www\KaizenCloud\PHPMailer\PHPMailerAutoload.php';

error_reporting(0);
session_start();
// if (isset($_GET['getFactory'])) {
//     $getFactoryQuery = "SELECT GSBH,IP FROM PPH_Kaizen_Adjustment_Factory WHERE YN = 1";
//     $dataArr = array();
//     $recordFactoryQuery = odbc_exec($conn_eip, $getFactoryQuery) or die(odbc_errormsg());
//     while ($result = odbc_fetch_array($recordFactoryQuery)) {
//         $dataArr[] = $result;
//     };
//     echo json_encode($dataArr);
// }
function fetch_to_array($ID, $server = "192.168.30.4", $dbname = "HRIS", $id = "anubis", $pass = "02isD0nkey")
{
    $conn = new COM("ADODB.Connection", NULL, CP_UTF8) or die("Cannot start ADO");
    $conn_str = "Driver={SQL Server};Server={" . $server . "};Database=" . $dbname . ";UID=" . $id . ";PWD=" . $pass . ";";

    $conn->Open($conn_str);
    $sql = "select 
				base64_img1 = CAST('' AS XML).value('xs:base64Binary(sql:column(\"Person_Image\"))','VARCHAR(MAX)')
			from (
				select 
				Person_Image = cast(Person_Image as varbinary(max))
				from Data_Person WHERE Person_ID='" . $ID . "'
				) T";
    $rs = $conn->Execute($sql);
    $num_columns = $rs->Fields->Count();
    $data[] = null;
    while (!$rs->EOF) {
        $tmp_arr[] = null;
        for ($i = 0; $i < $num_columns; $i++) {
            $tmp_arr[(string)$rs->Fields[$i]->Name] = (string)$rs->Fields[$i]->Value;
        }
        unset($tmp_arr[0]);
        array_push($data, $tmp_arr);
        $rs->MoveNext();
        unset($tmp_arr);
    }
    unset($data[0]);
    $data = array_values($data);
    $rs->Close();
    $conn->Close();

    $rs = null;
    $conn = null;
    return $data[0]['base64_img1'];
}
//Lấy Giá Trị Nhà Máy
function getFactory($eipConnect)
{
    $query_factory = "SELECT GSBH,IP FROM PPH_Kaizen_Adjustment_Factory WHERE YN = 1";
    $result_factory = odbc_exec($eipConnect, $query_factory);
    $obj = array();
    while ($result = odbc_fetch_object($result_factory)) {
        $obj[] = $result;
    }
    return $obj;
}

//Lấy Giá Trị Chức Vụ
function getPosition($eipConnect)
{
    $query_position = "SELECT ID_Position,NameEN FROM PPH_Kaizen_Adjustment_Position WHERE YN = 1";
    $result_position = odbc_exec($eipConnect, $query_position);
    $obj = array();
    while ($result = odbc_fetch_object($result_position)) {
        $obj[] = $result;
    }
    return $obj;
}

//Lấy Giá Trị Vấn Đề
function getProblem($eipConnect)
{
    $query_problem = "SELECT ID_Problem,NameEN FROM PPH_Kaizen_Adjustment_Problem WHERE YN = 1";
    $result_problem = odbc_exec($eipConnect, $query_problem);
    $obj = array();
    while ($result = odbc_fetch_object($result_problem)) {
        $obj[] = $result;
    }
    return $obj;
}

//Lấy Giá Trị Đơn Vị
function getDepartment($eipConnect)
{
    $query_department = "SELECT deptcode,deptname,LTRIM(RTRIM(deptname_vn)) deptname_vn FROM PPH_Kaizen_Adjustment_Department WHERE YN = 1";
    $result_department = odbc_exec($eipConnect, $query_department);
    $obj = array();
    while ($result = odbc_fetch_object($result_department)) {
        $obj[] = $result;
    }
    return $obj;
}

//Lấy Giá Trị Line
function getLine($eipConnect)
{
    $query_line = "SELECT distinct line,  (Case when Len(SUBSTRING(PA.LINE, CHARINDEX( '-',PA. LINE)+1,2))=1 then   REPLACE(PA.LINE, '-', '_0') else REPLACE(PA.LINE, '-', '_') end) line_order FROM PPH_Line_IP PA where YN=1 order by line_order";
    $result_line = odbc_exec($eipConnect, $query_line);
    $obj = array();
    while ($result = odbc_fetch_object($result_line)) {
        $obj[] =  $result;
    }
    return $obj;
}

function getEventOfYear($eipConnect, $year, $month)
{
    $query_line = "select * from PPH_Kaizen_Adjustment_Event where MONTH(StartDate) = '" . $month . "' AND YEAR (StartDate) = '" . $year . "'";
    $result_line = odbc_exec($eipConnect, $query_line);
    $obj = array();
    while ($result = odbc_fetch_object($result_line)) {
        $obj[] =  $result;
    }
    return $obj;
}

function getCharColumn($eipConnect, $type, $year, $dept)
{
    if ($dept == 'ALL' || $dept == '') {
        $query_line = "select number, ISNULL(sl,0) sl
    from (select number from master..spt_values where type = 'P' and number between 1 and 12) a
    left join (SELECT  m thang,count(*) sl FROM (SELECT *, MONTH(KDate) m FROM PPH_Kaizen_Adjustment_Report WHERE YEAR(KDate) = '" . $year . "' AND Problem_Improve = '" . $type . "' AND ME_Status ='DONE') A GROUP BY m) b on a.number = b.thang";
    } else {
        $query_line = "select number, ISNULL(sl,0) sl
    from (select number from master..spt_values where type = 'P' and number between 1 and 12) a
    left join (SELECT  m thang,count(*) sl FROM (SELECT *, MONTH(KDate) m FROM PPH_Kaizen_Adjustment_Report WHERE YEAR(KDate) = '" . $year . "' AND Dept = '" . $dept . "' AND Problem_Improve = '" . $type . "' AND ME_Status ='DONE') A GROUP BY m) b on a.number = b.thang";
    }
    $result_line = odbc_exec($eipConnect, $query_line);
    $obj = array();
    while ($result = odbc_fetch_object($result_line)) {
        $obj[] = (int)$result->sl;
    }
    return $obj;
}

function getEmail($conn_eip, $col, $type) {
    $qry = "SELECT email 
            FROM PPH_Kaizen_Email_Setup
            WHERE $col = '$type'";  // thêm ''

    $rs = odbc_exec($conn_eip, $qry);
    $obj = array();

    while ($result = odbc_fetch_object($rs)) {
        $obj[] = $result->email; // chỉ lấy cột email
    }
    return $obj;
}

//Lấy Giá trị cho form add Improvement
// function getApartOfImprovement($eipConnect,$id){
//     $query_report ="select * from PPH_Kaizen_Adjustment_Report where ID ='".$id."'";
//     $result_report = odbc_exec($eipConnect,$query_report);
//     $obj = array();
//     while ($result = odbc_fetch_object($result_report)){
//         $obj[] = $result;
//     }
//     return $obj;
// }

    function create_email($to, $cc, $subject, $body, $tempFile = null)
    {
        $mail = new PHPMailer(true);
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'thienlhg0318@gmail.com'; // Gmail của bạn
            $mail->Password   = 'xwqd feja jtbn vojn';   // App password Gmail
            //$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            
            
        

            // Người gửi
            $mail->setFrom('thienlhg0318@gmail.com');

            if(!empty($to)){
                if(is_array($to)){
                    // Danh sách TO
                    $toList = $to;
                    foreach ($toList as $to) {
                        $mail->addAddress($to);
                    }
                }else{
                    $mail -> addAddress($to);
                }
            }else{
                return "Vui lòng nhập email !";
            }



            if (!empty($cc)) {
                // Danh sách CC
                if(is_array($cc)){
                    $ccList = $cc;
                    foreach ($ccList as $cc){
                        $mail -> addCC($cc);
                    }
                }else{
                    $mail->addCC($cc);
                } 
            }


            // Đính kèm file vào email
            if (!empty($tempFile)) {
                if (is_array( $tempFile)) {
                    foreach ( $tempFile as $file) {
                        if (file_exists($file)) {
                            $mail->addAttachment($file, basename($file));
                        }
                    }
                } else {
                    if (file_exists($tempFile)) {
                        $mail->addAttachment( $tempFile, basename( $tempFile));
                    }
                }
            }

            // Nội dung
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            return ['Msg' => 'Gửi email thành công'];
        } catch (Exception $e) {
            return ['Msg' => 'Gửi email thất bại.'];
        }
    }

function generateExcelReport($data){

    $objPHPExcel= new PHPExcel;
    $provinceSheet = $objPHPExcel->setActiveSheetIndex(0);
    
   
    $provinceSheet->setCellValue("A1", "DEPARTMENT")
                 ->setCellValue("B1", "TARGET")
                 ->setCellValue("C1", "SUBMITTED")
                 ->setCellValue("D1", "DONE")
                 ->setCellValue("E1", "ON-GOING")
                 ->setCellValue("F1", "FAILED")
                 ->setCellValue("G1", "% ACHIEVE");
    
    
    $i = 2; 
    
    foreach ($data as $value) {
        $provinceSheet
                 ->setCellValue("A$i", $value['deptName']) 
                 ->setCellValue("B$i", $value['target'])
                 ->setCellValue("C$i", $value['totalCases'])
                 ->setCellValue("D$i", $value['doneCases'])
                 ->setCellValue("E$i", $value['ongoingCases'])
                 ->setCellValue("F$i", $value['failedCases'])
                 ->setCellValue("G$i", $value['psAchieve']);
        $i++;
    }
    
   
    foreach(range('A','E') as $columnID) {
        $provinceSheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
   
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    
    
    $filename = 'report_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    
    $objWriter->save($filename);
    
    return $filename;
}




//Xử Lý API
// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit();
}

// Handle GET request from ReactJS
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if ($_GET['api'] == 'getInput') {
        $result = (object)[
            'FACTORY' => getFactory($conn_eip),
            'POSITION' => getPosition($conn_eip),
            'PROBLEM' => getProblem($conn_eip),
            'DEPARTMENT' => getDepartment($conn_eip),
            'LEAN' => getLine($conn_eip),
        ];
        //$arrayRs[] = $result;
        echo json_encode($result);
        exit();
    }

    if ($_GET['api'] == 'getDepartment') {
        $result = getDepartment($conn_eip);
        echo json_encode($result);
        exit();
    }
    if ($_GET['api'] == 'getDataRaw') {
        $dt = $_GET['dt'];
        $type = $_GET['id'];
        $dept = $_GET['dep'];
        $status = $_GET['status'];

        if ($dt == "") {
            $dt = date("Y-m");
        }
        //Phân Trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $rows;

        $y = substr($dt, 0, 4);
        $m = substr($dt, 5, 2);
        //Lấy total 
        if ($type == "" && $dept != "All" && $status != "All" && $status != "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "' and PCI_Status = '" . $status . "' ";
        } elseif ($type == "" && $dept != "All" && $status == "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "' and PCI_Status is null  ";
        } elseif ($type == "" && $dept == "All" && $status == "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and PCI_Status is null  ";
        } elseif ($type == "" && $dept == "All"  && $status != "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and PCI_Status = '" . $status . "'";
        } elseif ($type == "" && $dept == "All" && $status == "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'";
        } elseif ($type == "" && $dept != "All" && $status == "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "'";
        } else {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'";
        }
        $rs = odbc_exec($conn_eip, $sql);

        $total = odbc_result($rs, 1);
        //
        if ($type == "" && $dept != "All" && $status != "All" && $status != "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "' and PKAR.PCI_Status = '" . $status . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept != "All" && $status == "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "' and PKAR.PCI_Status is null  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status == "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and PKAR.PCI_Status is null  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status != "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and PKAR.PCI_Status = '" . $status . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status == "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept != "All" && $status == "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "' ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } else {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        }
        //$getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        //echo json_encode(array('SQL' => $getDataRaw));
        $dataArr = array();
        $recordQuery = odbc_exec($conn_eip, $getDataRaw) or die(odbc_errormsg());
        while ($result = odbc_fetch_array($recordQuery)) {
            $dataArr[] = $result;
        };
        header('x-total-count: ' . $total);
        echo json_encode($dataArr);
    }

    //get data raw for me
    if ($_GET['api'] == 'getDataRawMe') {
        $dt = $_GET['dt'];
        $type = $_GET['id'];
        $dept = $_GET['dep'];
        $status = $_GET['status'];

        if ($dt == "") {
            $dt = date("Y-m");
        }
        //Phân Trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $rows;

        $y = substr($dt, 0, 4);
        $m = substr($dt, 5, 2);
        //Lấy total 
        //Lấy total 
        if ($type == "" && $dept != "All" && $status != "All" && $status != "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "' and Quality = '" . $status . "' and PCI_Status = 'DONE' ";
        } elseif ($type == "" && $dept != "All" && $status == "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "' and Quality is null and PCI_Status = 'DONE' ";
        } elseif ($type == "" && $dept == "All" && $status == "REQUIRECF") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and Quality is null and PCI_Status = 'DONE' ";
        } elseif ($type == "" && $dept == "All"  && $status != "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'  and Quality = '" . $status . "' and PCI_Status = 'DONE'";
        } elseif ($type == "" && $dept == "All" && $status == "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'and PCI_Status = 'DONE'";
        } elseif ($type == "" && $dept != "All" && $status == "All") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Dept ='" . $dept . "'and PCI_Status = 'DONE'";
        } else {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'and PCI_Status = 'DONE'";
        }
        $rs = odbc_exec($conn_eip, $sql);

        $total = odbc_result($rs, 1);
        //
        if ($type == "" && $dept != "All" && $status != "All" && $status != "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "' and PKAR.Quality = '" . $status . "' and PKAR.PCI_Status = 'DONE'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept != "All" && $status == "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "' and PKAR.PCI_Status = 'DONE' and PKAR.Quality is null  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status == "REQUIRECF") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.PCI_Status = 'DONE'  and PKAR.Quality is null  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status != "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.PCI_Status = 'DONE'  and PKAR.Quality = '" . $status . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept == "All" && $status == "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.PCI_Status = 'DONE'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($type == "" && $dept != "All" && $status == "All") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.Dept ='" . $dept . "'and PKAR.PCI_Status = 'DONE'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } else {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and PKAR.PCI_Status = 'DONE'  and Problem_Improve = '" . $type . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        }
        //$getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        //echo json_encode(array('SQL' => $getDataRaw));
        $dataArr = array();
        $recordQuery = odbc_exec($conn_eip, $getDataRaw) or die(odbc_errormsg());
        while ($result = odbc_fetch_array($recordQuery)) {
            $dataArr[] = $result;
        };
        header('x-total-count: ' . $total);
        echo json_encode($dataArr);
    }




    //get data for library
    if ($_GET['api'] == 'getDataLibrary') {
        $dt = $_GET['dt'];
        $type = $_GET['id'];
        $no = $_GET['no'];
        $search = $_GET['search'];
        //get all report of top 3
        $userid = $_GET['userid'];
        $quarter = $_GET['type'];

        if ($dt == "") {
            $dt = date("Y-m");
        }
        //Phân Trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $rows;

        $y = substr($dt, 0, 4);
        $m = substr($dt, 5, 2);
        //Lấy total 
        if ($search == "") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'  ";
        } elseif ($userid != "" && $quarter == "year") {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "'  and Person_ID = '" . $userid . "'";
        } elseif ($userid != "" && $quarter == "1") {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 1 AND 3  and Person_ID = '" . $userid . "'";
        } elseif ($userid != "" && $quarter == "2") {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 4 AND 6  and Person_ID = '" . $userid . "'";
        } elseif ($userid != "" && $quarter == "3") {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 7 AND 9  and Person_ID = '" . $userid . "'";
        } elseif ($userid != "" && $quarter == "4") {

            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 10 AND 12  and Person_ID = '" . $userid . "'";
        } else {
            $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_Report where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'  and Person_ID like'%" . $search . "%'";
        }


        $rs = odbc_exec($conn_eip, $sql);

        $total = odbc_result($rs, 1);
        //
        if ($no != "") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where   ID='" . $no . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($search != "") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'  and Person_ID like'%" . $search . "%'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($userid != "" && $quarter == "year") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "'   and  Person_ID = '" . $userid . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($userid != "" && $quarter == "1") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 1 AND 3    and  Person_ID = '" . $userid . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($userid != "" && $quarter == "2") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 4 AND 6    and  Person_ID = '" . $userid . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($userid != "" && $quarter == "3") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 7 AND 9    and  Person_ID = '" . $userid . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } elseif ($userid != "" && $quarter == "4") {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' AND MONTH(KDate) BETWEEN 10 AND 12    and  Person_ID = '" . $userid . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        } else {
            $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' and Problem_Improve = '" . $type . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        }


        //$getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "' ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";
        //echo json_encode(array('SQL' => $getDataRaw));
        $dataArr = array();
        $recordQuery = odbc_exec($conn_eip, $getDataRaw) or die(odbc_errormsg());
        while ($result = odbc_fetch_array($recordQuery)) {
            $dataArr[] = $result;
        };
        header('x-total-count: ' . $total);
        echo json_encode($dataArr);
    }
    //end

    // get data for SmartTool 


    // get data for SmartTool New
    if ($_GET['api'] == 'getDataSmartTools') {
        $dt = $_GET['dt'];

        if ($dt == "") {
            $dt = date("Y-m");
        }
        //Phân Trang
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $rows = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $rows;

        $y = substr($dt, 0, 4);
        $m = substr($dt, 5, 2);
        //Lấy total 
        $sql = "SELECT COUNT(*) total FROM PPH_Kaizen_Adjustment_SmartTool where YEAR(UserDate) = '" . $y . "' and MONTH(UserDate) = '" . $m . "'";
        $rs = odbc_exec($conn_eip, $sql);
        $total = odbc_result($rs, 1);
        //
        $getDataRawSmartTool = "SELECT * FROM PPH_Kaizen_Adjustment_SmartTool  where YEAR(UserDate) = '" . $y . "' and MONTH(UserDate) = '" . $m . "'  ORDER BY ID OFFSET " . $offset . " ROWS FETCH NEXT " . $rows . " ROWS ONLY";

        //echo json_encode($getDataRawSmartTool);
        $dataArr = array();
        $recordQuery = odbc_exec($conn_eip, $getDataRawSmartTool) or die(odbc_errormsg());
        while ($result = odbc_fetch_array($recordQuery)) {
            $dataArr[] = $result;
        };
        header('x-total-count: ' . $total);
        echo json_encode($dataArr);
    }
    //-------------


    if ($_GET['api'] == 'getRow') {
        $id = $_GET['id'];
        $getDataRaw = "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where id='" . $id . "'";
        //echo json_encode(array('SQL' => $getDataRaw));
        $dataArr = array();
        $recordQuery = odbc_exec($conn_eip, $getDataRaw) or die(odbc_errormsg());
        while ($result = odbc_fetch_array($recordQuery)) {
            $dataArr[] = $result;
        };
        echo json_encode($dataArr);
    }

    if ($_GET['api'] == 'getEventOfYear') {
        $year = $_GET['year'];
        $result = (object)[
            'JANUARY' => getEventOfYear($conn_eip, $year, "1"),
            'FEBRUARY' => getEventOfYear($conn_eip, $year, "2"),
            'MARCH' => getEventOfYear($conn_eip, $year, "3"),
            'APRIL' => getEventOfYear($conn_eip, $year, "4"),
            'MAY' => getEventOfYear($conn_eip, $year, "5"),
            'JUNE' => getEventOfYear($conn_eip, $year, "6"),
            'JULY' => getEventOfYear($conn_eip, $year, "7"),
            'AUGUST' => getEventOfYear($conn_eip, $year, "8"),
            'SEPTEMBER' => getEventOfYear($conn_eip, $year, "9"),
            'OCTOBER' => getEventOfYear($conn_eip, $year, "10"),
            'NOVEMBER' => getEventOfYear($conn_eip, $year, "11"),
            'DECEMBER' => getEventOfYear($conn_eip, $year, "12"),
        ];

        //$arrayRs[] = $result;
        echo json_encode($result);
        exit();
    }
    if ($_GET['api'] == 'getShowEvents') {
        $y = date("Y");
        $m = date("m");
        $d = date("d");

        $sqlGetShow = "select top 4  EventName,StartDate,Location,EndDate,DAY(StartDate) [Day],Month(StartDate) [Month], SUBSTRING(CONVERT(varchar(20),CONVERT(TIME,StartDate)), 1, 5)  [Time]  from PPH_Kaizen_Adjustment_Event where Year(StartDate) = '" . $y . "'  AND ((MONTH(StartDate) = '" . $m . "'  and Day(StartDate) >= '" . $d . "' ) OR  MONTH(StartDate) > '" . $m . "')  And Status='Show' order by StartDate";
        $rs = odbc_exec($conn_eip, $sqlGetShow);
        if (odbc_num_rows($rs) > 0) {
            $data = array();
            while ($row = odbc_fetch_array($rs)) {
                $data[] = $row;
            }

            // $response = array('Msg' => 'Successfully!.', 'Data' => $data);
            http_response_code(201);
            echo json_encode($data);
        } else {
            $response = array('Msg' => 'Fail!.');
            // Đặt mã trạng thái không thành công
            http_response_code(400);
            echo json_encode($response);
        }
        exit();
    }

    // if($_GET['api']=='getReport'){
    //     $id = $_GET['id'];
    //     $query_report ="select * from PPH_Kaizen_Adjustment_Report where ID ='".$id."'";
    //     $result_report = odbc_exec($conn_eip,$query_report);
    //     if(odbc_num_rows($result_report)> 0){
    //         $obj = array();
    //         while ($result = odbc_fetch_object($result_report)){
    //             $obj[] = $result;
    //         }
    //         http_response_code(201);
    //         echo json_encode($obj);
    //     }
    //     else{
    //         $response = array('Msg' => 'Fail!.');
    //         // Đặt mã trạng thái không thành công
    //         http_response_code(400);
    //         echo json_encode($response);
    //     }
    // }
    if ($_GET['api'] == 'getReport') {
        $id = $_GET['id'];
        $query_report =  "SELECT PKAR.*,PKAD.deptname,'H' + ' ' +  SUBSTRING(CONVERT(varchar(4),YEAR(KDate)),3,2) + '-' + case when len(MONTH(KDate)) = 1 then '0' + CONVERT(varchar(2),MONTH(KDate)) else CONVERT(varchar(2),MONTH(KDate)) end +  ' ' + ISNULL(Dept , '') + ' ' + case when len(Rf_Number) = 1 then '0000' + CONVERT(varchar(1),Rf_Number) when len(Rf_Number) = 2 then '000' + CONVERT(varchar(2),Rf_Number) when len(Rf_Number) = 3 then '00' + CONVERT(varchar(3),Rf_Number) when len(Rf_Number) = 4 then '0' + CONVERT(varchar(4),Rf_Number) when len(Rf_Number) = 5 then CONVERT(varchar(5),Rf_Number) ELSE '' END  as Refferen_Number FROM PPH_Kaizen_Adjustment_Report PKAR LEFT JOIN PPH_Kaizen_Adjustment_Department PKAD ON PKAR.Dept = PKAD.deptcode where id='" . $id . "'";
        $result_report = odbc_exec($conn_eip, $query_report);
        if (odbc_num_rows($result_report) > 0) {
            $obj = odbc_fetch_object($result_report); // Lấy một đối tượng từ kết quả truy vấn
            http_response_code(201);
            echo json_encode($obj);
        } else {
            $response = array('Msg' => 'Fail!.');
            // Đặt mã trạng thái không thành công
            http_response_code(400);
            echo json_encode($response);
        }
    }

    // get Session
    if ($_GET['api'] == 'getSession') {
        // $userid = @$_SERVER['HTTP_X_USERID'];
        $userid = $_GET['userId'];

        $lv = "";
        $dep = "";
        //check level
        $sql  = "SELECT Level,DepName FROM PPH_Kaizen_Adjustment_User WHERE UserID = '" . $userid . "'";
        $rs = odbc_exec($conn_eip, $sql);
        $lv = odbc_result($rs, 1);
        $dep = odbc_result($rs, 'DepName');
        $result = (object)[
            'UserID' => $userid,
            'Level' => $lv,
            'DepName' => $dep
        ];
        echo json_encode($result);
        exit();
    }





    //get All Document
    if ($_GET['api'] == 'getAllDocument') {
        $query = "SELECT * from PPH_Kaizen_Adjustment_Education";
        $result = odbc_exec($conn_eip, $query);
        $obj = array();
        while ($row = odbc_fetch_object($result)) {
            $obj[] = $row;
        }
        http_response_code(201);
        echo json_encode($obj);
    }

    //get a Document
    if ($_GET['api'] == 'getDetailDocument') {
        $id = $_GET['id'];
        $query = "SELECT * from PPH_Kaizen_Adjustment_Education where ID= '" . $id . "'";
        $result = odbc_exec($conn_eip, $query);
        $obj = odbc_fetch_object($result);
        http_response_code(201);
        echo json_encode($obj);
    }

    //get _Adjustment_Improvement
    if ($_GET['api'] == 'getConfirmImprovement') {
        $id = $_GET['id'];
        $query = "SELECT * from PPH_Kaizen_Adjustment_Improvement where ID='" . $id . "'";
        $result = odbc_exec($conn_eip, $query);
        if (odbc_num_rows($result) > 0) {
            $obj = odbc_fetch_object($result); // Lấy một đối tượng từ kết quả truy vấn
            http_response_code(201);
            echo json_encode($obj);
        } else {
            $response = array('Msg' => 'Fail!.');
            // Đặt mã trạng thái không thành công
            http_response_code(400);
            echo json_encode($response);
        }
    }

    //get VideoSmartTool
    if ($_GET['api'] == 'getVideoSmartTool') {
        $id = $_GET['id'];
        $query = "SELECT * from PPH_Kaizen_Adjustment_SmartTool WHERE ID= '" . $id . "'";
        $result = odbc_exec($conn_eip, $query);


        if (odbc_num_rows($result) > 0) {
            $obj = odbc_fetch_object($result); // Lấy một đối tượng từ kết quả truy vấn
            http_response_code(201);
            echo json_encode($obj);
        } else {
            $response = array('Msg' => 'Fail!.');

            http_response_code(400);
            echo json_encode($response);
        }
    }

    //get donut chart
    if ($_GET['api'] == 'getDonutChart') {
        $year = $_GET['year'];
        $month = $_GET['month'];

        // if ($date == 'month') {
        //     $query = "SELECT Problem_Improve,CONVERT(decimal(10,2),Item) * 100 / TOTAL* 1.0  [PERCENT] FROM (SELECT Problem_Improve,COUNT(*) Item,(SELECT COUNT(*) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE MONTH(KDate) = MONTH(GETDATE()) AND YEAR(KDate) = YEAR(GETDATE()) AND Status ='DONE' ) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE MONTH(KDate) = MONTH(GETDATE()) and YEAR(KDate) = YEAR(GETDATE()) AND Status ='DONE' GROUP BY Problem_Improve)A order by Problem_Improve";
        // } else if ($date == 'year') {
        //     $query = "SELECT Problem_Improve,CONVERT(decimal(10,2),Item) * 100 / TOTAL* 1.0  [PERCENT] FROM (SELECT Problem_Improve,COUNT(*) Item,(SELECT COUNT(*) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE YEAR(KDate) = YEAR(GETDATE()) AND Status ='DONE' ) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE YEAR(KDate) = YEAR(GETDATE()) AND Status ='DONE' GROUP BY Problem_Improve)A order by Problem_Improve";
        // }

        $query = "SELECT Problem_Improve,CONVERT(decimal(10,2),Item) * 100 / TOTAL* 1.0  [PERCENT] FROM (SELECT Problem_Improve,COUNT(*) Item,(SELECT COUNT(*) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE MONTH(KDate) = '" . $month . "' AND YEAR(KDate) = '" . $year . "' AND ME_Status ='DONE' ) TOTAL FROM PPH_Kaizen_Adjustment_Report WHERE MONTH(KDate) = '" . $month . "' and YEAR(KDate) = '" . $year . "' AND Status ='DONE' GROUP BY Problem_Improve)A order by Problem_Improve";

        $result = odbc_exec($conn_eip, $query);

        if (odbc_num_rows($result) > 0) {
            $data = array();
            while ($row = odbc_fetch_array($result)) {
                $data[] = $row;
            }
            // $response = array('Msg' => 'Successfully!.', 'Data' => $data);
            http_response_code(201);
            echo json_encode($data);
        } else {
            $response = [];
            // Đặt mã trạng thái không thành công
            http_response_code(400);
            echo json_encode($response);
        }
    }

    //get column chart
    if ($_GET['api'] == 'getColumnChart') {
        $year = $_GET['year'];
        $dept = $_GET['dept'];
        $result = array(
            array('name' => 'EFFICIENCY', 'data' => getCharColumn($conn_eip, 'EFFICIENCY', $year, $dept)),
            array('name' => 'QUALITY', 'data' => getCharColumn($conn_eip, 'QUALITY', $year, $dept)),
            array('name' => 'COST_SAVINGS', 'data' => getCharColumn($conn_eip, 'COST_SAVINGS', $year, $dept)),
            array('name' => 'JOB_SPEED_UP', 'data' => getCharColumn($conn_eip, 'JOB_SPEED_UP', $year, $dept)),
            array('name' => '5S_SAFETY', 'data' => getCharColumn($conn_eip, '5S_SAFETY', $year, $dept))
        );

        // Encode the result as JSON
        echo json_encode($result);
        exit();
    }


    //get Top 3 
    if ($_GET['api'] == 'getTop3') {
        $year = $_GET['year'];
        $season = $_GET['season'];

        if ($season == 'ALL' || $season == '') {
            $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark  FROM PPH_Kaizen_Adjustment_Improvement WHERE YEAR(UserDate) = '" . $year . "' GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        } else {
            $sql = "SELECT from_month, to_month FROM PPH_Kaizen_Season_Setup WHERE season = '" . $season . "'";
            $rs = odbc_exec($conn_eip, $sql);
            $from_month = odbc_result($rs, 1);
            $to_month = odbc_result($rs, 2);
            $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark FROM PPH_Kaizen_Adjustment_Improvement WHERE  CONVERT(date,UserDate) BETWEEN '" . $from_month . "' AND '" . $to_month . "' GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        }

        // if ($type == '1') {
        //     $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark FROM PPH_Kaizen_Adjustment_Improvement WHERE Year(UserDate) =  Year(GETDATE())  AND MONTH(UserDate) BETWEEN 1 AND 3 GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        // }
        // if ($type == '2') {
        //     $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark FROM PPH_Kaizen_Adjustment_Improvement WHERE Year(UserDate) =  Year(GETDATE())  AND MONTH(UserDate) BETWEEN 4 AND 6 GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        // }
        // if ($type == '3') {
        //     $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark FROM PPH_Kaizen_Adjustment_Improvement WHERE Year(UserDate) =  Year(GETDATE())  AND MONTH(UserDate) BETWEEN 7 AND 9 GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        // }
        // if ($type == '4') {
        //     $query = "SELECT A.UserID, A.TotalMark,dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(B.Person_Name)) Person_Name FROM ( SELECT TOP 3 UserID, SUM(Mark) AS TotalMark FROM PPH_Kaizen_Adjustment_Improvement WHERE Year(UserDate) =  Year(GETDATE())  AND MONTH(UserDate) BETWEEN 10 AND 12 GROUP BY UserID ORDER BY TotalMark DESC ) A LEFT JOIN HRISPROCE.HRIS.dbo.Data_Person B on A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = B.Person_ID ORDER BY TotalMark DESC";
        // }

        $result = odbc_exec($conn_eip, $query);

        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $row["img"] = fetch_to_array($row["UserID"]);
            $data[] = $row;
        }
        // $response = array('Msg' => 'Successfully!.', 'Data' => $data);
        http_response_code(201);
        echo json_encode($data);
    }

    //get Top 3 in Month
    if ($_GET['api'] == 'getTop3InMonth') {
        $dt = $_GET['dt'];

        if ($dt == "") {
            $dt = date("Y-m");
        }

        $y = substr($dt, 0, 4);
        $m = substr($dt, 5, 2);

        $query = "SELECT 
                        A.UserID, 
                        A.ID,
                        A.ImgAfter,
                        A.Mark,
                        B.Refferen_Number, 
                        B.Title_Improve,
                        B.Problem_Improve,
                        dbo.fTCVNToUnicode(dbo.fChuyenCoDauThanhKhongDau(C.Person_Name)) AS Person_Name,
                        B.After_Improve
                        
                    FROM (
                        SELECT TOP 3 * 
                        FROM PPH_Kaizen_Adjustment_Improvement
                        WHERE 
                            YEAR(UserDate) = '" . $y . "' AND  
                            MONTH(UserDate) = '" . $m . "'
                        ORDER BY Mark DESC
                    ) A
                    LEFT JOIN (
                        SELECT 
                            PKAR.*,
                            PKAD.deptname,
                            'H' + ' ' +  
                            SUBSTRING(CONVERT(VARCHAR(4), YEAR(KDate)), 3, 2) + '-' + 
                            CASE 
                                WHEN LEN(MONTH(KDate)) = 1 THEN '0' + CONVERT(VARCHAR(2), MONTH(KDate)) 
                                ELSE CONVERT(VARCHAR(2), MONTH(KDate)) 
                            END + ' ' +
                            ISNULL(Dept, '') + ' ' + 
                            CASE 
                                WHEN LEN(Rf_Number) = 1 THEN '0000' + CONVERT(VARCHAR(1), Rf_Number)
                                WHEN LEN(Rf_Number) = 2 THEN '000' + CONVERT(VARCHAR(2), Rf_Number)
                                WHEN LEN(Rf_Number) = 3 THEN '00' + CONVERT(VARCHAR(3), Rf_Number)
                                WHEN LEN(Rf_Number) = 4 THEN '0' + CONVERT(VARCHAR(4), Rf_Number)
                                WHEN LEN(Rf_Number) = 5 THEN CONVERT(VARCHAR(5), Rf_Number)
                                ELSE ''
                            END AS Refferen_Number
                        FROM 
                            PPH_Kaizen_Adjustment_Report PKAR
                        LEFT JOIN 
                            PPH_Kaizen_Adjustment_Department PKAD 
                            ON PKAR.Dept = PKAD.deptcode
                        WHERE 
                            YEAR(KDate) = '" . $y . "'
                    ) B 
                        ON A.ID = B.ID
                    LEFT JOIN 
                        HRISPROCE.HRIS.dbo.Data_Person C 
                        ON A.UserID COLLATE Chinese_Taiwan_Stroke_CI_AS = C.Person_ID";
        $result = odbc_exec($conn_eip, $query);
        //echo json_encode(array('SQL' => $query));
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        // $response = array('Msg' => 'Successfully!.', 'Data' => $data);
        http_response_code(201);
        echo json_encode($data);
    }


    if ($_GET['api'] == 'getModel') {
        $art = $_GET['art'];

        $query = "select DDZL.article, xieming model from LIY_ERP.LIY_ERP.DBO.DDZL --xieming : tên giày
            left join LIY_ERP.LIY_ERP.DBO.XXZl on DDZL.XieXing=XXZl.XieXing and DDZL.SheHao=XXZl.SheHao  
            where DDZL.ARTICLE='$art'
            group by DDZL.ARTICLE,xieming";;

        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode($data);
    }

    if ($_GET['api'] == 'getTarget') {
        $year = $_GET['year'];
        $dept = $_GET['dept'];

        if ($dept == "ALL" || $dept == "") {
            $query = "select t.*, d.deptname, d.deptname_vn from PPH_Kaizen_Monthly_Case_Target t
                left join PPH_Kaizen_Adjustment_Department d on t.department = d.deptcode  where yn= 1  AND t.year = '" . $year . "'";
        } else {
            $query = "select t.*, d.deptname, d.deptname_vn from PPH_Kaizen_Monthly_Case_Target t
                left join PPH_Kaizen_Adjustment_Department d on t.department = d.deptcode  where yn= 1 AND d.deptcode = '" . $dept . "' AND t.year = '" . $year . "'";
        }


        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode($data);
    }

    if ($_GET['api'] == 'getSeason') {
        $query = "SELECT        id, from_month, to_month, season, userID, userdate FROM PPH_Kaizen_Season_Setup";
        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode($data);
    }

    if ($_GET['api'] == 'getReportOfYearMonth') {

        $year = $_GET['year'];
        $month = $_GET['month'];

        // Get department stats with done/failed percentages
        $query = "SELECT 
                        K.deptname,
                        T.target,
                        COUNT(*) as TotalCases,
                        SUM(CASE WHEN ME_Status = 'DONE' THEN 1 ELSE 0 END) as DoneCases,
                        SUM(CASE WHEN ME_Status = 'ONGOING' THEN 1 ELSE 0 END) as OngoingCases,
                        CAST(CAST(SUM(CASE WHEN ME_Status = 'DONE' THEN 1 ELSE 0 END) AS FLOAT) * 100 / 
                            CAST(COUNT(*) AS FLOAT) AS DECIMAL(10,2)) as DonePercent,
                        SUM(CASE WHEN Status = 'FAIL' THEN 1 ELSE 0 END) as FailedCases,
                        CAST(CAST(SUM(CASE WHEN Status = 'FAIL' THEN 1 ELSE 0 END) AS FLOAT) * 100 / 
                            CAST(COUNT(*) AS FLOAT) AS DECIMAL(10,2)) as FailPercent
                    FROM PPH_Kaizen_Adjustment_Report r
                    JOIN PPH_Kaizen_Monthly_Case_Target T 
                        ON T.department = r.Dept
                    JOIN PPH_Kaizen_Adjustment_Department k
                        ON k.deptcode = r.Dept
                    WHERE YEAR(KDate) = $year AND MONTH(KDate) = $month
                    AND T.year = $year AND T.month = $month
                    GROUP BY K.deptname, T.target";
                        
        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }

        http_response_code(200);
        echo json_encode($data);
        exit();
        
    }

    if ($_GET['api'] === 'getMonthDoneEmail') {
  
        $year = $_GET['year'];
    
        try {
            $sql = "
                SELECT 
                    MONTH(KDate) AS month,
                    COUNT(*) AS total_sent
                FROM PPH_Kaizen_Adjustment_Report
                WHERE status = 'DONE'
                AND YEAR(KDate) = $year
                GROUP BY MONTH(KDate)
                ORDER BY MONTH(KDate)
            ";
            $rs = odbc_exec($conn_eip, $sql);

            // Mặc định mảng 12 tháng với giá trị 0
            $data = array_fill(1, 12, 0);

            // Lặp kết quả
            while (odbc_fetch_row($rs)) {
                $month = intval(odbc_result($rs, "month"));
                $total = intval(odbc_result($rs, "total_sent"));
                $data[$month] = $total;
            }

            echo json_encode($data);

        } catch (Exception $e) {
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
    //get dept set up

    if( $_GET['api'] === 'getDeptSetup') {
        $query = "
            SELECT  S.kaizen_dept as id,
            D.deptname as name
            FROM PPH_Kaizen_Email_Setup S
            JOIN PPH_Kaizen_Adjustment_Department D
            ON S.kaizen_dept = D.deptcode
        ";
        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode($data);
    }

    if($_GET['api'] === 'getAllEmailSetup') {
        $query = "
            SELECT 
                id,
                userID,
                kaizen_dept as departmentId,
                email, 
                trial_stage,
                auto_report
            FROM PPH_Kaizen_Email_Setup ";
        $result = odbc_exec($conn_eip, $query);
        $data = array();
        while ($row = odbc_fetch_array($result)) {
            $data[] = $row;
        }
        http_response_code(200);
        echo json_encode($data);
    }

}


// Handle POST request from ReactJS
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($_GET['api'] == 'AddEducation') {
        $Title  = $_POST['Title'];
        $UserID = $_POST['UserID'];

        $FileDocument = $_FILES["DocumentName"]["name"];
        //Thư mục bạn sẽ lưu file upload
        $target_dir_file    = "Uploads/Files/";
        //Vị trí file lưu tạm trong server (file sẽ lưu trong uploads, với tên giống tên ban đầu)
        $target_file   = $target_dir_file . basename($_FILES["DocumentName"]["name"]);

        move_uploaded_file($_FILES["DocumentName"]["tmp_name"], $target_file);


        $FilePhoto = $_FILES["CoverPhoto"]["name"];
        //Thư mục bạn sẽ lưu Image upload
        $target_dir_img    = "Uploads/Images/";

        //Những loại file được phép upload
        $allowtypes    = array('jpg', 'png', 'jpeg', 'gif');

        //Đổi tên file
        $tempBe = explode(".", $FilePhoto);
        $extBe = end($tempBe); // lấy phần mở rộng
        $newfilenameBe = 'Cover-' . round(microtime(true)) . '.' . $extBe;
        //Vị trí file lưu tạm trong server (file sẽ lưu trong uploads)
        $target_fileBe   =  $target_dir_img . basename($newfilenameBe);
        //Lấy phần mở rộng của file 
        $FileTypeBe = pathinfo($target_fileBe, PATHINFO_EXTENSION);

        // Kiểm tra kiểu file
        // if (!in_array($FileTypeBe, $allowtypes)) {
        //     //$allowUpload = false;
        //     echo json_encode(array('Info' => 'Ảnh Không Đúng Định Dạng !.'));
        //     return;
        // }

        //upload file
        move_uploaded_file($_FILES["CoverPhoto"]["tmp_name"], $target_fileBe);

        $sql  = "INSERT INTO PPH_Kaizen_Adjustment_Education(UserID,UserDate,DocumentName,Title,CoverPhoto) VALUES ('" . $UserID . "',GETDATE(),'" . $FileDocument . "',N'" . $Title . "','" . $newfilenameBe . "')";

        $rs = odbc_exec($conn_eip, $sql);
        if ($rs > 0) {
            echo json_encode(array('Info' => "Add successfully!"));
        } else {
            echo json_encode(array('Info' => "Add Fail!"));
        }
    }

    if ($_GET['api'] == 'postInput') {
        // Lấy dữ liệu từ yêu cầu POST
        // $json = file_get_contents("php://input");
        // $data = json_decode($json, true);

        // Xử lý dữ liệu và trả về kết quả
        $KDate = $_POST["KDate"];
        $GSBH  = $_POST["GSBH"];
        $Dept  = $_POST["Dept"];
        $Position  = $_POST["Position"];
        $Person_ID = $_POST["Person_ID"];
        $Lean = $_POST["Lean"];
        $Article = $_POST["Article"];
        $Model  = $_POST["Model"];
        $Problem_Improve = $_POST["Problem_Improve"];
        $Title_Improve = $_POST["Title_Improve"];
        $Before_Improve = $_POST["Before_Improve"];
        $After_Improve = $_POST["After_Improve"];
        $Phone = $_POST["Phone"];
        $Email = $_POST["Email"];

        // $Status = $data[""];
        // $Quality = $data[""];

        //Tạo Reference Number
        $sql = "SELECT COUNT(*) + 1 from PPH_Kaizen_Adjustment_Report where  YEAR(KDate) = '" . date('Y', strtotime($KDate)) . "' and MONTH(KDate) = '" . date('m', strtotime($KDate)) . "' and Dept = '" . $Dept . "'";
        $rs = odbc_exec($conn_eip, $sql);
        $Rf_Number = odbc_result($rs, 1);
        // $Rf_Number = $data[""];
        //Tạo Model Name
        if ($Article != '' && $Model == '') {
            $check_model = "select XieMing from LIY_ERP.LIY_ERP.DBO.kfxxzl where article ='$Article'";
            $rs_model    = odbc_exec($conn_eip, $check_model);
            $Model   = odbc_result($rs_model, 1);
        }
        //check ID
        $qry_id = "select count(1) from PPH_Kaizen_Adjustment_Report where KDate = '$KDate'"; //echo $qry_id;
        $rs_id = odbc_result(odbc_exec($conn_eip, $qry_id), 1);
        if ($rs_id == 0) {
            $id = '001';
            $id = date('Ymd', strtotime(date($KDate))) . $id;
            //$id = $id + 1;
        } else {
            $qry_maxid = "select max(right(ID,3))+1 from PPH_Kaizen_Adjustment_Report where KDate = '$KDate'"; //echo $qry_maxid;
            $rs_id     = odbc_exec($conn_eip, $qry_maxid);
            $id        = odbc_result($rs_id, 1);

            while (strlen($id) < 3) {
                $id = '0' . $id;
            }
            $id   = date('Ymd', strtotime(date($KDate))) . $id;
        }

        // $InsertSQL = "INSERT INTO PPH_Kaizen_Adjustment_Report(ID,KDate,GSBH,Dept,Position,Person_ID,Lean,Model,Article,Problem_Improve,Title_Improve,Before_Improve,After_Improve,Rf_Number,UserDate,Phone,Email)VALUES ('" . $id . "','" . $KDate . "','" . $GSBH . "','" . $Dept . "','" . $Position . "','" . $Person_ID . "',N'" . $Lean . "',N'" . $Model . "','" . $Article . "','" . $Problem_Improve . "','" . $Title_Improve . "',N'" . $Before_Improve . "',N'" . $After_Improve . "'," . $Rf_Number . ",GETDATE(),'" . $Phone . "','" . $Email . "')";
        $InsertSQL = "INSERT INTO PPH_Kaizen_Adjustment_Report(ID,KDate,GSBH,Dept,Position,Person_ID,Lean,Model,Article,Problem_Improve,Title_Improve,Before_Improve,After_Improve,Rf_Number,UserDate)VALUES ('" . $id . "','" . $KDate . "','" . $GSBH . "','" . $Dept . "','" . $Position . "','" . $Person_ID . "',N'" . $Lean . "',N'" . $Model . "','" . $Article . "','" . $Problem_Improve . "','" . $Title_Improve . "',N'" . $Before_Improve . "',N'" . $After_Improve . "'," . $Rf_Number . ",GETDATE())";

        // echo json_encode(array('SQL' => $InsertSQL));
        // die;
        $rs = odbc_exec($conn_eip, $InsertSQL);

        if (odbc_num_rows($rs) > 0) {
            // Tạo thư mục nếu chưa có
            $target_dir_img = "Uploads/Images/";
            if (!is_dir($target_dir_img)) mkdir($target_dir_img, 0777, true);
            $allowtypes  = array('jpg', 'png', 'jpeg', 'gif');

            // Biến lưu tên file (nếu có)
            $newfilenameBefore = '';
            $newfilenameAf = '';


            // 🟡 Xử lý ảnh Before nếu có
            if (isset($_FILES["Before_Improve_Img"])) {
                $FilePhotoBefore = $_FILES["Before_Improve_Img"]["name"];
                $tempBefore = explode(".", $FilePhotoBefore);
                $newfilenameBefore = 'B' . round(microtime(true)) . '.' . end($tempBefore);
                $target_fileBefore = $target_dir_img . $newfilenameBefore;

                $FileTypeBefore = pathinfo($target_fileBefore, PATHINFO_EXTENSION);
                $ImgBeforeSize = $_FILES["Before_Improve_Img"]["size"];

                if (!in_array(strtolower($FileTypeBefore), $allowtypes)) {
                    echo json_encode(['Msg' => 'Ảnh Before không đúng định dạng!']);
                    return;
                }
                if ($ImgBeforeSize > 5 * 1024 * 1024) {
                    echo json_encode(['Msg' => 'Ảnh Before vượt quá dung lượng 5MB']);
                    return;
                }

                move_uploaded_file($_FILES["Before_Improve_Img"]["tmp_name"], $target_fileBefore);
            }

            // 🟡 Xử lý ảnh After nếu có
            if (isset($_FILES["After_Improve_Img"])) {
                $FilePhotoAf = $_FILES["After_Improve_Img"]["name"];
                $tempAf = explode(".", $FilePhotoAf);
                $newfilenameAf = 'A' . round(microtime(true)) . '.' . end($tempAf);
                $target_fileAf = $target_dir_img . $newfilenameAf;

                $FileTypeAf = pathinfo($target_fileAf, PATHINFO_EXTENSION);
                $ImgAfterSize = $_FILES["After_Improve_Img"]["size"];

                if (!in_array(strtolower($FileTypeAf), $allowtypes)) {
                    echo json_encode(['Msg' => 'Ảnh After không đúng định dạng!']);
                    return;
                }
                if ($ImgAfterSize > 5 * 1024 * 1024) {
                    echo json_encode(['Msg' => 'Ảnh After vượt quá dung lượng 5MB']);
                    return;
                }

                move_uploaded_file($_FILES["After_Improve_Img"]["tmp_name"], $target_fileAf);
            }

            //  Nếu có ít nhất 1 ảnh thì INSERT ảnh vào bảng phụ
            if (!empty($newfilenameBefore) || !empty($newfilenameAf)) {
                $sql = "INSERT INTO PPH_Kaizen_Adjustment_Improvement(ID, ImgBefore, ImgAfter,UserCF)
                VALUES('" . $id . "', '" . $newfilenameBefore . "', '" . $newfilenameAf . "' , 'create')";
                odbc_exec($conn_eip, $sql);
            }

            $response = array('Msg' => 'Add successfully!.', 'Data' => $data);
            http_response_code(201);
            echo json_encode($response);
        } else {
            $response = array('Msg' => 'Add Fail!.');
            http_response_code(400);
            echo json_encode($response);
        }


        exit();
    }
    if ($_GET['api'] == 'postEvent') {
        // Lấy dữ liệu từ yêu cầu POST
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        // Xử lý dữ liệu và trả về kết quả
        $EventName = $data["EventName"];
        $StartDate = $data["StartDate"];
        $EndDate = $data["EndDate"];
        $Location = $data["Location"];

        $InsertSQL = "INSERT INTO PPH_Kaizen_Adjustment_Event(EventName,StartDate,Location,UserDate,EndDate)VALUES ('" . $EventName . "','" . $StartDate . "',N'" . $Location . "',GETDATE(),'" . $EndDate . "')";
        $rs = odbc_exec($conn_eip, $InsertSQL);

        if (odbc_num_rows($rs) > 0) {
            $response = array('Msg' => 'Add successfully!.', 'Data' => $data);
            //$response = array('Data' => $data);
            // Đặt mã trạng thái thành công (201)
            http_response_code(201);
            echo json_encode($response);
        } else {

            $response = array('Msg' => 'Add Fail!.');
            // Đặt mã trạng thái không thành công
            http_response_code(400);
            echo json_encode($response);
        }
        exit();
    }

    // if ($_GET['api'] == 'Post_Improvement') {
    //     $ID  = $_POST['id'];
    //     $UserID  = '51401';
    //     $NoOfUnit  = $_POST['NoOfUnit'];
    //     $Effectivity  = $_POST['Effectivity'];
    //     $MChange  = $_POST['MChange'];
    //     // $ImgBefore  = $_POST['ImgBefore'];
    //     // $ImgAfter  = $_POST['ImgAfter'];
    //     $CtBefore  = $_POST['CtBefore'];
    //     $CtAfter  = $_POST['CtAfter'];
    //     $TtBefore = $_POST['TtBefore'];
    //     $TtAfter  = $_POST['TtAfter'];
    //     $WorkingTimeBefore_hrs  = $_POST['WorkingTimeBefore_hrs'];
    //     $WorkingTimeAfter_hrs  = $_POST['WorkingTimeAfter_hrs'];
    //     $CheckSumBefore  = $_POST['CheckSumBefore'];
    //     $CheckSumAfter  = $_POST['CheckSumAfter'];
    //     $ErrorBefore  = $_POST['ErrorBefore'];
    //     $ErrorAfter  = $_POST['ErrorAfter'];
    //     $QuantityTarget  = $_POST['QuantityTarget'];
    //     $TimeComplete_min  = $_POST['TimeComplete_min'];
    //     $ConstBefore  = $_POST['ConstBefore'];
    //     $ConstAfter  = $_POST['ConstAfter'];
    //     $SaftBefroe  = $_POST['SaftBefroe'];
    //     $SaftAfter  = $_POST['SaftAfter'];
    //     $Adjustment  = $_POST['Adjustment'];
    //     $Refine  = $_POST['Refine'];
    //     $Clean  = $_POST['Clean'];
    //     $Standardization  = $_POST['Standardization'];
    //     $Nurture  = $_POST['Nurture'];
    //     $ContentBefore  = $_POST['ContentBefore'];
    //     $ContentAfter  = $_POST['ContentAfter'];

    //     //CHECK TỒN TẠI
    //     $sql = "SELECT * FROM PPH_Kaizen_Adjustment_Improvement WHERE ID = '" . $ID . "'";
    //     $rs = odbc_exec($conn_eip, $sql);

    //     if (odbc_num_rows($rs) > 0) {
    //         $sql1 = "";
    //     } else {
    //         $sql1 = "INSERT INTO PPH_Kaizen_Adjustment_Improvement (ID,UserID,UserDate,NoOfUnit,Effectivity,MChange,ImgBefore,ImgAfter,CtBefore,CtAfter,TtBefore,TtAfter,WorkingTimeBefore_hrs,WorkingTimeAfter_hrs,CheckSumBefore,CheckSumAfter,ErrorBefore,ErrorAfter,QuantityTarget,TimeComplete_min,ConstBefore,ConstAfter,SaftBefroe,SaftAfter,Adjustment,Refine,Clean,Standardization,Nurture,ContentBefore,ConstAfter) VALUES ('" . $ID . "', '51401', GETDATE(),'" . $NoOfUnit . "', '" . $Effectivity . "' , '" . $MChange . "', '', '' , " . $CtBefore . ", " . $CtAfter . ", " . $TtBefore . " , " . $TtAfter . ", " . $WorkingTimeBefore_hrs . ", " . $WorkingTimeAfter_hrs . "," . $CheckSumBefore . ", " . $CheckSumAfter . " , " . $ErrorBefore . " , " . $ErrorAfter . " , " . $QuantityTarget . ", " . $TimeComplete_min . " , " . $ConstBefore . " , " . $ConstAfter . " ," . $SaftBefroe . " , " . $SaftAfter . " , '" . $Adjustment . "', '" . $Refine . "' , '" . $Clean . "', '" . $Standardization . "', '" . $Nurture . "', '" . $ContentBefore . "','" . $ConstAfter . "')";
    //     }
    //     echo json_encode(array('Msg' => $sql1));
    //     // $rs1 = odbc_exec($conn_eip, $sql1);

    //     // if (odbc_num_rows($rs1) > 0) {
    //     //     echo json_encode(array('Msg' => 'Successfully.'));
    //     // } else {
    //     //     echo json_encode(array('Msg' => 'Fail.'));
    //     // }
    //     // exit();
    // }


    if ($_GET['api'] == 'Confirm_Improvement') {
        $ID = $_POST['ID'];
        $UserID = $_POST['UserID'];
        $NoOfUnit = $_POST['NoOfUnit'];
        $Effectivity = $_POST['Effectivity'];
        $MChange = $_POST['MChange'];
        $FilePhotoBefore = $_FILES["ImgBefore"]["name"];
        $FilePhotoAf = $_FILES["ImgAfter"]["name"];
        $CtBefore = $_POST['CtBefore'];
        $CtAfter = $_POST['CtAfter'];
        $TtBefore = $_POST['TtBefore'];
        $TtAfter = $_POST['TtAfter'];
        $WorkingTimeBefore_hrs = $_POST['WorkingTimeBefore_hrs'];
        $WorkingTimeAfter_hrs = $_POST['WorkingTimeAfter_hrs'];
        $CheckSumBefore = $_POST['CheckSumBefore'];
        $CheckSumAfter = $_POST['CheckSumAfter'];
        $ErrorBefore = $_POST['ErrorBefore'];
        $ErrorAfter = $_POST['ErrorAfter'];
        $QuantityTarget = $_POST['QuantityTarget'];
        $TimeCompleteBefore  = $_POST['TimeCompleteBefore'];
        $TimeCompleteAfter  = $_POST['TimeCompleteAfter'];
        $WorkingTime = $_POST['WorkingTime'];
        $NumPeopleBefore      = $_POST['NumPeopleBefore'];
        $NumPeopleAfter    = $_POST['NumPeopleAfter'];
        $CostBefore = $_POST['CostBefore'];
        $CostAfter = $_POST['CostAfter'];
        $Safe = $_POST['Safe'];
        $CostSaving = $_POST['CostSaving'];
        $Adjustment = $_POST['Adjustment'];
        $Refine = $_POST['Refine'];
        $Clean = $_POST['Clean'];
        $Standardization = $_POST['Standardization'];
        $Nurture = $_POST['Nurture'];
        $ContentBefore = $_POST['ContentBefore'];
        $ContentAfter = $_POST['ContentAfter'];
        $UserCF = $_POST['UserCF'];
        $Mark = $_POST['Mark'];

        // Tạo thư mục nếu chưa có
        $target_dir_img = "Uploads/Images/";
        if (!is_dir($target_dir_img)) mkdir($target_dir_img, 0777, true);
        $allowtypes  = array('jpg', 'png', 'jpeg', 'gif');

        // Biến lưu tên file (nếu có)
        $newfilenameBefore = '';
        $newfilenameAf = '';

        // 🟡 Xử lý ảnh Before nếu có
        if (isset($_FILES["ImgBefore"])) {
            $FilePhotoBefore = $_FILES["ImgBefore"]["name"];
            $tempBefore = explode(".", $FilePhotoBefore);
            $newfilenameBefore = 'B' . round(microtime(true)) . '.' . end($tempBefore);
            $target_fileBefore = $target_dir_img . $newfilenameBefore;

            $FileTypeBefore = pathinfo($target_fileBefore, PATHINFO_EXTENSION);
            $ImgBeforeSize = $_FILES["ImgBefore"]["size"];

            if (!in_array(strtolower($FileTypeBefore), $allowtypes)) {
                echo json_encode(['Msg' => 'Ảnh Before không đúng định dạng!']);
                return;
            }
            if ($ImgBeforeSize > 5 * 1024 * 1024) {
                echo json_encode(['Msg' => 'Ảnh Before vượt quá dung lượng 5MB']);
                return;
            }

            move_uploaded_file($_FILES["ImgBefore"]["tmp_name"], $target_fileBefore);
        }

        // 🟡 Xử lý ảnh After nếu có
        if (isset($_FILES["ImgAfter"])) {
            $FilePhotoAf = $_FILES["ImgAfter"]["name"];
            $tempAf = explode(".", $FilePhotoAf);
            $newfilenameAf = 'A' . round(microtime(true)) . '.' . end($tempAf);
            $target_fileAf = $target_dir_img . $newfilenameAf;

            $FileTypeAf = pathinfo($target_fileAf, PATHINFO_EXTENSION);
            $ImgAfterSize = $_FILES["ImgAfter"]["size"];

            if (!in_array(strtolower($FileTypeAf), $allowtypes)) {
                echo json_encode(['Msg' => 'Ảnh After không đúng định dạng!']);
                return;
            }
            if ($ImgAfterSize > 5 * 1024 * 1024) {
                echo json_encode(['Msg' => 'Ảnh After vượt quá dung lượng 5MB']);
                return;
            }

            move_uploaded_file($_FILES["ImgAfter"]["tmp_name"], $target_fileAf);
        }


        $sql  = "INSERT  into PPH_Kaizen_Adjustment_Improvement(ID,UserID,UserDate,NoOfUnit,Effectivity,MChange,ImgBefore,ImgAfter,CtBefore,CtAfter,TtBefore,TtAfter,WorkingTimeBefore_hrs,WorkingTimeAfter_hrs,CheckSumBefore,CheckSumAfter,ErrorBefore,ErrorAfter,QuantityTarget,TimeCompleteBefore,TimeCompleteAfter,WorkingTime,NumPeopleBefore,NumPeopleAfter,CostBefore,CostAfter,Safe,CostSaving,Adjustment,Refine,Clean,Standardization,Nurture,ContentBefore,ContentAfter,UserCF,Mark)VALUES('" . $ID . "','" . $UserID . "',GETDATE(),'" . $NoOfUnit . "','" . $Effectivity . "','" . $MChange . "','" . $newfilenameBefore . "','" . $newfilenameAf . "','" . $CtBefore . "','" . $CtAfter . "','" . $TtBefore . "','" . $TtAfter . "','" . $WorkingTimeBefore_hrs . "','" . $WorkingTimeAfter_hrs . "','" . $CheckSumBefore . "','" . $CheckSumAfter . "','" . $ErrorBefore . "','" . $ErrorAfter . "','" . $QuantityTarget . "','" . $TimeCompleteBefore . "','" . $TimeCompleteAfter . "','" . $WorkingTime . "','" . $NumPeopleBefore . "','" . $NumPeopleAfter . "','" . $CostBefore . "','" . $CostAfter . "','" . $Safe . "','" . $CostSaving . "','" . $Adjustment . "','" . $Refine . "','" . $Clean . "','" . $Standardization . "','" . $Nurture . "','" . $ContentBefore . "','" . $ContentAfter . "','" . $UserCF . "','" . $Mark . "')";
        // echo json_encode(array('Info' => $sql1));
        $rs = odbc_exec($conn_eip, $sql);
        if ($rs > 0) {
            echo json_encode(array('Msg' => "Add successfully!"));
        } else {
            echo json_encode(array('Msg' => $sql));
        }
    }
    if ($_GET['api'] == 'Update_Improvement') {
        $ID = $_POST['ID'];
        $UserID = $_POST['UserID'];
        $NoOfUnit = $_POST['NoOfUnit'];
        $Effectivity = $_POST['Effectivity'];
        $MChange = $_POST['MChange'];

        $CtBefore = $_POST['CtBefore'];
        $CtAfter = $_POST['CtAfter'];
        $TtBefore = $_POST['TtBefore'];
        $TtAfter = $_POST['TtAfter'];
        $WorkingTimeBefore_hrs = $_POST['WorkingTimeBefore_hrs'];
        $WorkingTimeAfter_hrs = $_POST['WorkingTimeAfter_hrs'];
        $CheckSumBefore = $_POST['CheckSumBefore'];
        $CheckSumAfter = $_POST['CheckSumAfter'];
        $ErrorBefore = $_POST['ErrorBefore'];
        $ErrorAfter = $_POST['ErrorAfter'];
        $QuantityTarget = $_POST['QuantityTarget'];
        $TimeCompleteBefore  = $_POST['TimeCompleteBefore'];
        $TimeCompleteAfter  = $_POST['TimeCompleteAfter'];
        $WorkingTime = $_POST['WorkingTime'];
        $NumPeopleBefore      = $_POST['NumPeopleBefore'];
        $NumPeopleAfter    = $_POST['NumPeopleAfter'];
        $CostBefore = $_POST['CostBefore'];
        $CostAfter = $_POST['CostAfter'];
        $Safe = $_POST['Safe'];
        $CostSaving = $_POST['CostSaving'];
        $Adjustment = $_POST['Adjustment'];
        $Refine = $_POST['Refine'];
        $Clean = $_POST['Clean'];
        $Standardization = $_POST['Standardization'];
        $Nurture = $_POST['Nurture'];
        $ContentBefore = $_POST['ContentBefore'];
        $ContentAfter = $_POST['ContentAfter'];
        $UserCF = $_POST['UserCF'];
        $Mark = $_POST['Mark'];


        $FilePhotoBefore = $_FILES["ImgBefore"]["name"];
        $FilePhotoAf = $_FILES["ImgAfter"]["name"];

        // Check Exit
        $sqlCheckExit = "SELECT ImgBefore,ImgAfter FROM PPH_Kaizen_Adjustment_Improvement WHERE ID = '" . $ID . "'";
        $rsCheckExit = odbc_exec($conn_eip, $sqlCheckExit);
        $newfilenameBefore = odbc_result($rsCheckExit, 'ImgBefore');
        $newfilenameAf = odbc_result($rsCheckExit, 'ImgAfter');

        //Thư mục bạn sẽ lưu Image upload
        $target_dir_img = "Uploads/Images/";

        //Những loại file được phép upload
        $allowtypes  = array('jpg', 'png', 'jpeg', 'gif');


        if (isset($_FILES["ImgBefore"]) &&  $_FILES["ImgBefore"]["name"] != null) {
            //xóa ảnh củ
            if ($newfilenameBefore != '') {
                $link = $target_dir_img . $newfilenameBefore;
                $target_file   = $target_dir_img . basename($newfilenameBefore);
                if (file_exists($target_file)) {
                    unlink($link);
                }
            };
            //Đổi tên file
            $tempBefore = explode(".", $FilePhotoBefore);
            $newfilenameBefore = 'B' . round(microtime(true)) . '.' . end($tempBefore);
            //Vị trí file lưu tạm trong server (file sẽ lưu trong uploads)
            $target_fileBefore   =  $target_dir_img . basename($newfilenameBefore);
            //Lấy phần mở rộng của file 
            $FileTypeBefore = pathinfo($target_fileBefore, PATHINFO_EXTENSION);
            // Kiểm tra kiểu file
            if (!in_array($FileTypeBefore, $allowtypes)) {
                echo json_encode(array('Info' => 'Ảnh Không Đúng Định Dạng !.'));
                return;
            }
            //upload file
            move_uploaded_file($_FILES["ImgBefore"]["tmp_name"], $target_fileBefore);
        }

        if (isset($_FILES["ImgAfter"]) &&  $_FILES["ImgAfter"]["name"] != null) {
            //xóa ảnh củ
            if ($newfilenameAf != '') {
                $link = $target_dir_img . $newfilenameAf;
                $target_file   = $target_dir_img . basename($newfilenameAf);
                if (file_exists($target_file)) {
                    unlink($link);
                }
            };
            //Đổi tên file
            $tempAf = explode(".", $FilePhotoAf);
            $newfilenameAf = 'B' . round(microtime(true)) . '.' . end($tempAf);
            // Vị trí file lưu tạm trong server (file sẽ lưu trong uploads)
            $target_fileAf   =  $target_dir_img . basename($newfilenameAf);
            //Lấy phần mở rộng của file 
            $FileTypeAf = pathinfo($target_fileAf, PATHINFO_EXTENSION);

            // Kiểm tra kiểu file
            if (!in_array($FileTypeAf, $allowtypes)) {
                echo json_encode(array('Info' => 'Ảnh Không Đúng Định Dạng !.'));
                return;
            }

            //upload file
            move_uploaded_file($_FILES["ImgAfter"]["tmp_name"], $target_fileAf);
        }
        // Get the size of the uploaded images
        $ImgBeforeSize = $_FILES["ImgBefore"]["size"];
        $ImgAfterSize = $_FILES["ImgAfter"]["size"];
        // Define a maximum 
        $maxFileSize = 5 * 1024 * 1024; // 5 MB

        // // Check size
        if ($ImgBeforeSize > $maxFileSize || $ImgAfterSize > $maxFileSize) {
            $errorMessage = 'The photo is too big. Please upload a photo with a smaller size ' . ($maxFileSize / (1024 * 1024)) . ' MB.';
            echo json_encode(array('Msg' => $errorMessage));
            return;
        }

        $sql = "UPDATE PPH_Kaizen_Adjustment_Improvement SET UserDate=GETDATE(),NoOfUnit='" . $NoOfUnit . "',Effectivity = '" . $Effectivity . "',MChange ='" . $MChange . "',ImgBefore='" . $newfilenameBefore . "',ImgAfter='" . $newfilenameAf . "',CtBefore ='" . $CtBefore . "',CtAfter='" . $CtAfter . "',TtBefore='" . $TtBefore . "',TtAfter='" . $TtAfter . "',WorkingTimeBefore_hrs='" . $WorkingTimeBefore_hrs . "',WorkingTimeAfter_hrs='" . $WorkingTimeAfter_hrs . "',CheckSumBefore ='" . $CheckSumAfter . "',CheckSumAfter = '" . $CheckSumAfter . "' ,ErrorBefore ='" . $ErrorBefore . "',ErrorAfter='" . $ErrorAfter . "',QuantityTarget='" . $QuantityTarget . "',TimeCompleteBefore = '" . $TimeCompleteBefore . "',TimeCompleteAfter = '" . $TimeCompleteAfter . "',WorkingTime = '" . $WorkingTime . "',NumPeopleBefore = '" . $NumPeopleBefore . "',NumPeopleAfter = '" . $NumPeopleAfter . "',CostBefore = '" . $CostBefore . "',CostAfter ='" . $CostAfter . "' ,Safe = '" . $Safe . "',CostSaving='" . $CostSaving . "',Adjustment ='" . $Adjustment . "',Refine='" . $Refine . "',Clean='" . $Clean . "',Standardization='" . $Standardization . "',Nurture='" . $Nurture . "',ContentBefore ='" . $ContentBefore . "',ContentAfter='" . $ContentAfter . "',UserCF = '" . $UserCF . "', Mark = '" . $Mark . "' where ID ='" . $ID . "'";

        $rs = odbc_exec($conn_eip, $sql);
        if ($rs > 0) {
            echo json_encode(array('Msg' => "Add successfully!"));
        } else {
            echo json_encode(array('Msg' => $sql));
        }
    }

    //upload  video

    //upload  smartTool
    if ($_GET['api'] == 'UploadSmartTool') {
        $FileVideoBe = $_FILES["VideoBe"]['name'];
        $FileVideoAf = $_FILES["VideoAf"]['name'];
        $UserPosted = $_POST['UserPosted'];
        $Title = $_POST['Title'];
        $Model = $_POST['Model'];
        $Status = $_POST['Status'];
        $ctBefore = $_POST['ctBefore'];
        $ctAfter = $_POST['ctAfter'];
        $rftBefore = $_POST['rftBefore'];
        $rftAfter = $_POST['rftAfter'];
        $processBefore = $_POST['processBefore'];
        $processAfter = $_POST['processAfter'];
        $process = $_POST['process'];
        $expected = $_POST['expected'];
        // echo json_encode(array('Info' => $FileVideoBe ));
        // Check if the ID already exists in the database
        //   $checkIDSql = "SELECT ID FROM PPH_Kaizen_Adjustment_SmartTool WHERE ID = '".$ID."'";
        //   $checkResult = odbc_exec($conn_eip, $checkIDSql);

        //   if (odbc_num_rows($checkResult) > 0) {
        //     echo json_encode(array('Info' => 'ID already exists in the database.'));
        // } else {

        //Thư mục bạn sẽ lưu Image upload
        $target_dir_video = "Uploads/Videos/";
        $allowtypes = array('mp4', 'mov', 'avi', 'mkv', 'wmv', 'flv', 'webm');
        //Đổi tên file
        $tempBefore = explode(".", $FileVideoBe);
        $newfilenameBefore .= 'VideoBe-' . round(microtime(true)) . '.' . end($tempBefore);
        $tempAf = explode(".", $FileVideoAf);
        $newfilenameAf .= 'VideoAf-' . round(microtime(true)) . '.' . end($tempAf);
        //Vị trí file lưu tạm trong server (file sẽ lưu trong uploads)
        $target_fileBefore   =  $target_dir_video . basename($newfilenameBefore);
        $target_fileAf   =  $target_dir_video . basename($newfilenameAf);
        // //Lấy phần mở rộng của file 
        $FileTypeBefore = pathinfo($target_fileBefore, PATHINFO_EXTENSION);
        $FileTypeAf = pathinfo($target_fileAf, PATHINFO_EXTENSION);
        // Kiểm tra kiểu file
        if (!in_array(strtolower($FileTypeBefore), $allowtypes) || !in_array(strtolower($FileTypeAf), $allowtypes)) {
            echo json_encode(array('Msg' => 'Video capacity is too large, total capacity of 2 videos must less 98 MB.'));
            return;
        }

        //upload file
        move_uploaded_file($_FILES["VideoBe"]["tmp_name"], $target_fileBefore);
        move_uploaded_file($_FILES["VideoAf"]["tmp_name"], $target_fileAf);


        // Get the size of the uploaded images
        $VideoBeforeSize = $_FILES["VideoBe"]["size"];
        $VideoAfterSize = $_FILES["VideoAf"]["size"];
        // Define a maximum 
        $maxFileSize = 49 * 1024 * 1024; // 49 MB

        // // Check size
        if ($VideoBeforeSize > $maxFileSize && $VideoAfterSize > $maxFileSize) {
            $errorMessage = 'Video capacity is too large, total capacity of 2 videos must less 98 MB.';
            echo json_encode(array('Msg' => $errorMessage));
            return;
        }

        //check ID
        $currentDate = date('Y-m-d');
        $qry_id = "select count(1) from PPH_Kaizen_Adjustment_SmartTool where UserDate = '$currentDate'"; //echo $qry_id;
        $rs_id = odbc_result(odbc_exec($conn_eip, $qry_id), 1);
        if ($rs_id == 0) {
            $id = '01';
            $id = date('Ymd', strtotime(date($currentDate))) . $id;
            //$id = $id + 1;
        } else {
            $qry_maxid = "select max(right(ID,2))+1 from PPH_Kaizen_Adjustment_SmartTool where UserDate = '$currentDate'"; //echo $qry_maxid;
            $rs_id     = odbc_exec($conn_eip, $qry_maxid);
            $id        = odbc_result($rs_id, 1);

            while (strlen($id) < 2) {
                $id = '0' . $id;
            }
            $id   = date('Ymd', strtotime(date($currentDate))) . $id;
        }


        $sql  = "INSERT INTO PPH_Kaizen_Adjustment_SmartTool(ID,VideoBe,VideoAf, UserPosted, UserDate, Title,Model,Status,ctBefore,ctAfter,rftBefore,rftAfter,processBefore,processAfter,process,expected) VALUES ('" . $id . "','" . $newfilenameBefore . "','" . $newfilenameAf . "','" . $UserPosted . "', getDate(),'" . $Title . "','" . $Model . "','" . $Status . "','" . $ctBefore . "','" . $ctAfter . "','" . $rftBefore . "','" . $rftAfter . "','" . $processBefore . "','" . $processAfter . "','" . $process . "','" . $expected . "')";
        // echo json_encode(array('Info' => $sql));
        $rs = odbc_exec($conn_eip, $sql);
        if ($rs > 0) {
            echo json_encode(array('Msg' => "Add successfully!"));
        } else {
            //   echo json_encode(array('Msg' => $sql));
            echo json_encode(array('Msg' => $sql));
        }

        // }


    }
    if ($_GET['api'] === 'sendEmailReport') {
        $json = file_get_contents("php://input");
        $month = isset($_GET['month']) ? (int)$_GET['month'] : null;
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        // Chuyển JSON thành mảng
        $data = json_decode($json, true);
        echo json_encode($data);

        $actual = 0;
        $target = 0;
        
        foreach ($data as $key => $value) {
            $actual += $value['totalCases'];
            $target += $value['target'];
        }

        $diff = $target > 0 ? round((($actual / $target) - 1) * 100, 2) : 0;

        // Tạo file Excel
        $xlc = generateExcelReport($data);

        // lấy dử liệu email từ data
        // $toEmail = getEmail($conn_eip, "trial_stage", "TO");
        // $ccEmail = getEmail($conn_eip, "auto_report", "CC");

        // echo json_encode($toEmail);
        // echo json_encode($ccEmail);

        // Thông tin email
        $to = ['thien879811@gmail.com'];
        $cc = null;



        $subject = "Kaizen Monthly Report - {$year} {$month}";
        $body = "<p>Dear All,</p>
        <p>Vui lòng nhận báo cáo Kaizen tháng <b>{$month} {$year}</b>. 
        Chi tiết của báo cáo vui lòng xem ở tệp đính kèm.</p>

        <p><b>KAIZEN THÁNG {$month}:</b></p>

        <table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; text-align:center;'>
            <tr style='background:#f2f2f2;'>
                <th>Đơn vị</th>
                <th>Mục tiêu</th>
                <th>Thực tế</th>
                <th>Chênh lệch (%)</th>
            </tr>
            <tr>
                <td>LYV</td>
                <td>{$target}</td>
                <td>{$actual}</td>
                <td>{$diff}%</td>
            </tr>
        </table>

        <br>
        <p><i>(Chênh lệch = (thực tế/mục tiêu - 1) x 100%)</i></p>

        <br>
        <p>Thank and best regards!</p>

        <hr>
        <p>
            <b>Triệu Phước Toàn</b><br>
            Phone: +84358269547<br>
            Dept: GME-Lac Ty 2 Co, Ltd.<br>
            Lot B1, B2 Tan Thu Thanh Industrial Zone, Chau Thanh A District, Hau Giang Province
        </p>";

        // Gửi email
        $sendEmail = create_email($to, $cc, $subject, $body, $xlc);

        // Xóa file Excel sau khi gửi
        if (file_exists($xlc)) {
            unlink($xlc);
        }

        // Nếu có month thì update DB
        if (!empty($month)) {
            $sql = "
                UPDATE PPH_Kaizen_Adjustment_Report
                SET status = 'DONE'
                WHERE MONTH(KDate) = $month
                AND YEAR(KDate) = YEAR(GETDATE())
            ";
            $rs = odbc_exec($conn_eip, $sql);

        }

        // Chỉ trả về 1 JSON hợp lệ
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sendEmail);
    }
    if ($_GET['api'] === 'sendEmail') {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        $departmentsID = $data['departmentsID'];
        $to = $data['to'];
        $cc = $data['cc'];
        $id_report = $data['id_report'];
        // Kiểm tra xem departmentsID có phải là mảng hay không , chuyển thành chuỗi
        if (is_array($departmentsID)) {
            $departmentsID = "'" . implode("','", $departmentsID) . "'";
        }

        // Lấy thông tin improvement từ data

        $imQuery = "SELECT *  FROM PPH_Kaizen_Adjustment_Report WHERE ID = '" . $id_report . "'";
        $imResult = odbc_exec($conn_eip, $imQuery);
        if (!$imResult) {
            // Lấy thông tin lỗi
            $error = odbc_errormsg($conn_eip);
            die("SQL Error: " . $error . " | Query: " . $imQuery);
        }

        $dtImprovemrnt = odbc_fetch_array($imResult);

        if (!$dtImprovemrnt) {
            echo json_encode(['Msg' => 'Không tìm thấy thông tin cải tiến.']);
            return;
        }
        $nImprove = $dtImprovemrnt['Title_Improve'];
        $KDate = $dtImprovemrnt['KDate'];

        $subject = "Thông tin cải tiến: Tham gia buổi thử nghiệm ý tưởng đề xuất cải tiến";
        $body ="
            <p>Dear all,</p>
            <p>Vui lòng xem thông tin bên dưới là thông tin thử nghiệm của đề xuất cải tiến. 
            Mời các đơn vị liên quan bên dưới tham gia trực tiếp thử nghiệm.</p>

            <p><b>Vấn đề cải tiến:</b> <span style='color:orange;'>$nImprove</span><br>
            <b>Đơn vị tham gia:</b> <span style='color:orange;'>$departmentsID</span><br>
            <b>Thời gian:</b> <span style='color:orange;'>$KDate</span><br>
            <b>Địa điểm:</b> ..............................................</p>

            <p>Kính mong các đơn vị liên quan tham gia đúng thời gian buổi thử nghiệm.<br>
            Nếu anh/chị có thắc mắc vui lòng liên hệ FME.</p>

            <p>Số điện thoại nội bộ: <span style='color:orange;'>[số điện thoại theo từng nhà máy]</span></p>

            <p>
                <a href='http://192.168.30.19/KaizenCloud/4/KaizenCloud/rawData/Med?id=$id_report'>
                    Đường dẫn Kaizen
                </a>
            </p>

            <p>
                <img src='https://via.placeholder.com/600x250.png?text=Hinh+anh+minh+hoa'
                    alt='Hình ảnh minh họa' width='600'>
            </p>

            <p>Xin cảm ơn,<br>FME</p>
            <p><i>Thank and best regards!</i></p>

            <hr>
            <p>
                <b>Triệu Phước Toàn</b><br>
                Phone: +84358269547<br>
                Dept: GME-Lac Ty 2 Co, Ltd.<br>
                Lot B1, B2 Tan Thu Thanh Industrial Zone, Chau Thanh A District, Hau Giang Province
            </p>
            ";



        $qry = "SELECT Dept 
            FROM PPH_Kaizen_Adjustment_Department_CFM 
            WHERE Dept IN ($departmentsID)
        ";

        $rs = odbc_exec($conn_eip, $qry);

        if (!$rs) {
            // Lấy thông tin lỗi
            $error = odbc_errormsg($conn_eip);
            die("SQL Error: " . $error . " | Query: " . $qry);
        }

        $departments = [];
        while ($row = odbc_fetch_array($rs)) {
            $departments[] = $row['Dept'];
        }


        // đơn vị được chọn gửi email không có trong db thì gửi email
        // Lấy danh sách đơn vị từ request
        $requestedDepartments = is_array($data['departmentsID']) ? $data['departmentsID'] : [];
        // Lấy danh sách đơn vị đã có trong DB
        $existingDepartments = $departments;

        // Tìm các đơn vị chưa có trong DB (chỉ gửi email cho các đơn vị này)
        $departmentsToSend = array_diff($requestedDepartments, $existingDepartments);

        // Nếu không có đơn vị nào cần gửi thì trả về thông báo
        if (empty($departmentsToSend)) {
            $sendEmail = ['Msg' => 'Không có đơn vị nào cần gửi email.'];
        } else {
            if (empty($to)) {
            $sendEmail = ['Msg' => 'Không tìm thấy email của các đơn vị cần gửi.'];
            } else {

            // Thêm từng đơn vị vào bảng PPH_Kaizen_Adjustment_Department_CFM
            // foreach ($departmentsToSend as $dept) {
            //     $qr = "INSERT INTO PPH_Kaizen_Adjustment_Department_CFM (ID_Report, Dept, Status)
            //     VALUES ('" . $data['id'] . "', '" . $dept . "', '1')";
            //     odbc_exec($conn_eip, $qr);
            // }
            // $cc = $data['cc']; // Nếu có CC thì lấy ở đây
            $cc = null;
            $sendEmail = create_email($to, $cc, $subject, $body);
            }
        }
        // Chỉ trả về 1 JSON hợp lệ
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sendEmail);
    }

    if($_GET['api'] == 'diffDepartmentCFM') {
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        echo json_encode($data);
    }
}

// Handle DELETE request from ReactJS
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    if ($_GET['api'] == 'deleteDataRaw') {
        $id = $_GET['id'];
        //delete improvement
        $SelectSQL = "SELECT * from PPH_Kaizen_Adjustment_Improvement where ID = '" . $id . "'";
        $rs = odbc_exec($conn_eip, $SelectSQL);
        $FileNameImgBe = odbc_result($rs, 'ImgBefore');
        $FileNameImgAf = odbc_result($rs, 'ImgAfter');
        //duong dan
        $target_dir_img = "Uploads/Images/";
        if ($FileNameImgBe != '') {
            $link1 = $target_dir_img . $FileNameImgBe;
            $target_file1   =  $target_dir_img . basename($FileNameImgBe);
            //xoa file
            if (file_exists($target_file1)) {
                unlink($link1);
            }
        }

        if ($FileNameImgAf != '') {
            $link2 = $target_dir_img . $FileNameImgAf;
            $target_file2   = $target_dir_img . basename($FileNameImgAf);
            if (file_exists($target_file2)) {
                unlink($link2);
            }
        }

        $DeleteSQL = "DELETE from PPH_Kaizen_Adjustment_Improvement where ID='" . $id . "'";
        $rs = odbc_exec($conn_eip, $DeleteSQL);
        // echo json_encode(array('Info' => $DeleteSQL));
        //delete report
        $check_day = "select Rf_Number,KDate,Dept from PPH_Kaizen_Adjustment_Report where ID='$id'";
        $rs_ = odbc_exec($conn_eip, $check_day);
        if (odbc_num_rows($rs_) <= 0) {
            http_response_code(404);
            echo json_encode(array('Msg' => 'Fail.'));
            exit();
        }
        $rf_num = odbc_result($rs_, 'Rf_Number');
        $dept_code = odbc_result($rs_, 'Dept');
        $kdate =  odbc_result($rs_, 'KDate');
        $y = date("Y", strtotime($kdate));
        $m = date("m", strtotime($kdate));

        $sql = "delete FROM PPH_Kaizen_Adjustment_Report WHERE ID='$id'";

        $rs = odbc_exec($conn_eip, $sql);

        if ($rs > 0) {
            $sql = "UPDATE PPH_Kaizen_Adjustment_Report set Rf_Number = Rf_Number - 1 where Rf_Number > " . $rf_num . " and Dept = '" . $dept_code . "' and YEAR(KDate) = '" . $y . "' and MONTH(KDate) = '" . $m . "'";
            $rs = odbc_exec($conn_eip, $sql);
            echo json_encode(array('Msg' => 'Successfully.'));
        } else {
            echo json_encode(array('Msg' => 'Fail.'));
        }
    }

    if ($_GET['api'] == 'deleteEvent') {

        $id = $_GET['id'];
        $sql = "delete FROM PPH_Kaizen_Adjustment_Event WHERE IDEvent='$id'";
        $rs = odbc_exec($conn_eip, $sql);

        if ($rs > 0) {

            echo json_encode(array('Msg' => 'Successfully.'));
        } else {
            echo json_encode(array('Msg' => 'Fail.'));
        }
    }

    if ($_GET['api'] == "deleteDocument") {

        $ID = $_GET['id'];
        //lay file name
        $sql = "SELECT * from PPH_Kaizen_Adjustment_Education where ID = '" . $ID . "'";
        $rs = odbc_exec($conn_eip, $sql);
        $DocumentName = odbc_result($rs, 'DocumentName');
        $CoverPhoto = odbc_result($rs, 'CoverPhoto');
        //duong dan
        $target_dir_img = "Uploads/Images/";
        $target_dir_file    = "Uploads/Files/";


        if ($DocumentName != '') {
            $link1 =  $target_dir_file . $DocumentName;
            $target_file1   =   $target_dir_file . basename($DocumentName);
            //xoa file
            if (file_exists($target_file1)) {
                unlink($link1);
            }
        }
        if ($CoverPhoto  != '') {
            $link2 = $target_dir_img . $CoverPhoto;
            $target_file2   = $target_dir_img  . basename($CoverPhoto);
            if (file_exists($target_file2)) {
                unlink($link2);
            }
        }

        $DeleteSQL = "DELETE from PPH_Kaizen_Adjustment_Education where ID ='" . $ID . "'";
        $rs = odbc_exec($conn_eip, $DeleteSQL);
        // echo json_encode(array('Info' => $DeleteSQL));
        if (odbc_num_rows($rs) > 0) {
            echo json_encode(array('Info' => 'Xóa thành công!.'));
            return;
        } else {
            echo json_encode(array('Info' => 'Xóa thất bại!.'));
            return;
        }
    }

    // delete video
    if ($_GET['api'] == 'deleteDataVideo') {
        $id = $_GET['id'];
        //delete improvement
        $SelectSQL = "SELECT * from PPH_Kaizen_Adjustment_SmartTool where ID = '" . $id . "'";
        $rs = odbc_exec($conn_eip, $SelectSQL);
        $FileVideoBe = odbc_result($rs, 'VideoBe');
        $FileVideoAf = odbc_result($rs, 'VideoAf');
        //duong dan
        $target_dir_video = "Uploads/Videos/";
        if ($FileVideoBe != '') {
            $link1 = $target_dir_video . $FileVideoBe;
            $target_file1   =  $target_dir_video . basename($FileVideoBe);
            //xoa file
            if (file_exists($target_file1)) {
                unlink($link1);
            }
        }

        if ($FileVideoAf != '') {
            $link2 = $target_dir_video . $FileVideoAf;
            $target_file2   = $target_dir_video . basename($FileVideoAf);
            if (file_exists($target_file2)) {
                unlink($link2);
            }
        }

        $DeleteSQL = "DELETE from PPH_Kaizen_Adjustment_SmartTool where ID='" . $id . "'";
        $rs = odbc_exec($conn_eip, $DeleteSQL);

        //   echo json_encode(array('Info' => $DeleteSQL));
        if (odbc_num_rows($rs) > 0) {
            echo json_encode(array('Info' => 'Xóa thành công!.'));
            return;
        } else {
            echo json_encode(array('Info' => 'Xóa thất bại!.'));
            return;
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    if ($_GET['api'] == 'cfmStatus') {
        // Lấy dữ liệu từ yêu cầu POST
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        // Xử lý dữ liệu và trả về kết quả
        $id = $_GET['id'];
        $userid = $_GET['userCF'];
        $Status = $data["Status"];
        $sql = "UPDATE PPH_Kaizen_Adjustment_Report SET Status = '" . $Status . "',Status_CFMID = '" . $userid . "', Status_CFMDATE = GETDATE() WHERE id = '" . $id . "' ";
        $rs = odbc_exec($conn_eip, $sql);


        if ($rs > 0) {
            echo json_encode(array('Msg' => 'Successfully.'));
        } else {
            echo json_encode(array('Msg' => 'Fail.'));
        }
    }
    if ($_GET['api'] == 'cfmQuality') {
        // Lấy dữ liệu từ yêu cầu POST
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        // Xử lý dữ liệu và trả về kết quả
        $id = $_GET['id'];
        $Quality = $data["Quality"];
        $userid = $_GET['userCF'];

        $sql = "UPDATE PPH_Kaizen_Adjustment_Report SET Quality = '" . $Quality . "',Quality_CFMID = '" . $userid . "', Quality_CFMDATE = GETDATE() WHERE id = '" . $id . "' ";
        $rs = odbc_exec($conn_eip, $sql);

        if ($rs > 0) {
            echo json_encode(array('Msg' => 'Successfully.'));
        } else {
            echo json_encode(array('Msg' => 'Fail.'));
        }
    }

    if ($_GET['api'] == 'cfmEvent') {
        // Lấy dữ liệu từ yêu cầu POST
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        // Xử lý dữ liệu và trả về kết quả
        $id = $_GET['id'];
        $Status = $data["Status"];
        $sql = "UPDATE  PPH_Kaizen_Adjustment_Event SET Status = '" . $Status . "' WHERE IDEvent = '" . $id . "' ";
        $rs = odbc_exec($conn_eip, $sql);

        if ($rs > 0) {
            echo json_encode(array('Msg' => 'Successfully.'));
        } else {
            echo json_encode(array('Msg' => 'Fail.'));
        }
    }
}
