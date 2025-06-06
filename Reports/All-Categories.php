<?php
require __DIR__ . "/../vendor/autoload.php";
use Dompdf\Dompdf;
use Dompdf\Options;

include $_SERVER['DOCUMENT_ROOT'] . '../Database/connection.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');


$sql = "SELECT ID, `Description`,
        CASE WHEN `Status` = 1 THEN 'Active' ELSE 'Inactive' END AS `Status`  
        FROM lCategory
        ORDER BY `Status` ASC, `Description` ASC";

$result = $conn->query($sql);

$Categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $Categories[] = $row;
    }
}

$conn->close();

// *********************************
//  HTML STRUCTURE STARTS HERE
// *********************************

$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/reports.css">
</head>
<body>
    <h1>List of Categories as of {{ DateToday }} </h1>
    
    <table class="table-default">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="tableBody">';

foreach ($Categories as $Category) {
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($Category['Description']) . '</td>
                    <td>' . htmlspecialchars($Category['Status']) . '</td>
                </tr>';

}
$html .= "
            <tr><td colspan='8' textalign = center>- - - NOTHING'S FOLLOW - - -</td></tr> 
        </tbody>
    </table>
    
</body>
</html>";

$html = str_replace("{{ DateToday }}", date('F j, Y'), $html);

// *********************************
//  HTML STRUCTURE ENDS HERE
// *********************************

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('chroot', __DIR__);

$dompdf = new Dompdf($options);
$dompdf->setPaper('Letter', 'portrait');
$dompdf->loadHtml($html);

$dompdf->render();

$canvas = $dompdf->getCanvas();
$canvasWidth = $canvas->get_width();
$canvasHeight = $canvas->get_height();

session_start();
$LoggedinUser = $_SESSION['LoggedinUser'] ?? 'Unknown User';
$canvas->page_text(50, $canvasHeight - 75, 'Generated by: ' . $LoggedinUser, null, 8, array(0, 0, 0));

$currentDateTime = date('l, F j, Y h:i A');
$canvas->page_text(50, $canvasHeight - 60, 'Generated on: ' . $currentDateTime, null, 8, array(0, 0, 0));

$footerText = "Page {PAGE_NUM} of {PAGE_COUNT}";
$canvas->page_text($canvasWidth - 100, $canvasHeight - 75, $footerText, null, 10, array(0, 0, 0));


$dompdf->stream("List_Of_Items" . $currentDateTime . ".pdf", ["Attachment" => 0]);
?>