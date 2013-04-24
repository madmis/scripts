<?php
//============================================================+
// File name   : example_2d_html.php
// Version     : 1.0.000
// Begin       : 2011-07-21
// Last Update : 2013-03-17
// Author      : Nicola Asuni - Tecnick.com LTD - Manor Coach House, Church Hill, Aldershot, Hants, GU12 4RQ, UK - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2009-2013 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description : Example for tcpdf_barcodes_2d.php class
//
//============================================================+

/**
 * @file
 * Example for tcpdf_barcodes_2d.php class
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 * @version 1.0.009
 */

// include 2D barcode class
require_once(dirname(__FILE__) . '/../../tcpdf_barcodes_2d.php');

// set the barcode content and type
$barcodeobj = new TCPDF2DBarcode('http://www.tcpdf.org', 'PDF417');

// output the barcode as HTML object
echo $barcodeobj->getBarcodeHTML(4, 4, 'black');

//============================================================+
// END OF FILE
//============================================================+
