<?php
namespace ZedBoot\Utilities;
/**
 * @license     GNU General Public License, version 3
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2022 Jonathan Hulka
 */
interface TempFilesInterface
{
	/**
	 * @param ?string $data optional data to write to file
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *     'path' => (string)    <br>&emsp;
	 *     'size' => int         <br>&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function create(?string $data) : array;

	/**
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *     'path' => (string)    <br>&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function copy(string $path) : array;

	/**
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *     'path' => (string)    <br>&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function move(string $path) : array;

	/**
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *     'size' => (int),      <br>&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function append(string $path, string $data) : array;

	/**
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function delete(string $path) : array;

	/**
	 * @return array             <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'success',<br>&emsp;&emsp;
	 *   ]                       <br>&emsp;
	 *   OR                      <br>&emsp;
	 *   [                       <br>&emsp;&emsp;
	 *     'status' => 'error',  <br>&emsp;&emsp;
	 *     'message' => (string) <br>&emsp;
	 *   ]
	 */
	public function refresh(array $paths) : array;

	public function gc();
}
