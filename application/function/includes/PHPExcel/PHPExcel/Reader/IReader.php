<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2014 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2014 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    ##VERSION##, ##DATE##
 */


/**
 * PHPExcel_Reader_IReader
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2014 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
interface PHPExcel_Reader_IReader
{
	/**
	 * Can the current PHPExcel_Reader_IReader read the file?
	 *
	 * @param 	string 		$pFilename
	 * @return 	boolean
	 */
	public function canRead($pFilename);

	/**
	 * Loads PHPExcel from file
	 *
	 * @param 	string 		$pFilename
     * @return  PHPExcel
	 * @throws 	PHPExcel_Reader_Exception
	 */
	public function load($pFilename);

    /**
     * Set read data only
     *		Set to true, to advise the Reader only to read data values for cells, and to ignore any formatting information.
     *		Set to false (the default) to advise the Reader to read both data and formatting for cells.
     *
     * @param	boolean	$pValue
     *
     * @return	PHPExcel_Reader_IReader
     */
    public function setReadDataOnly($pValue = FALSE);

    /**
     * Validate that the current file is a CSV file
     *
     * @return boolean
     */
    public function _isValidFormat();
}
