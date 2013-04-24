<?php

require_once('tpdf.php');

if (isset($_GET['source'])) {
    echo '<div style="width: 200px; margin: 0 auto; margin-top: 200px;"><h2><a href="index.php?pdf">Create pdf</a></h2></div>';
    echo file_get_contents('html/v2.html');
} else if (isset($_GET['pdf'])) {
    $pdf = new tpdf();
    $pdf->setHtmlFile('html/v2.html');
    $pdf->setDocumentName('doc.pdf');

    $pdf->bindParams(array(
        '[USER_COMPANY]' => 'Company of User',
        '[DATE]' => date('Y.m.d'),
        '[EMPLOYEE_JOB]' => 'Employee job',
        '[ADDRESS]' => '228 Park Avenue South New York, NY 10003-1502.',
        '[City, STATE, ZIP Country]' => 'New York, NY 10003-1502',
        '[Date] ' => date('Y.m.d'),
        '[Signature]' => 'sign',
        '[fname+lname]' => 'Antony Dopkin',
        '[EMPLOYER_NAME]' => 'Employer name',
    ));

    $pdf->createDocument();
} else {
    echo '<div style="width: 200px; margin: 0 auto; margin-top: 200px;"><h2><a href="index.php?pdf">Create pdf</a></h2></div>';
    echo '<div style="width: 200px; margin: 0 auto; margin-top: 20px;"><h2><a href="index.php?source">Source html</a></h2></div>';
}

