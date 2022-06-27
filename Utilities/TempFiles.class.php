<?php
namespace ZedBoot\Utilities;
/**
 * @license     GNU General Public License, version 3
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2022 Jonathan Hulka
 */
class TempFiles implements \ZedBoot\Utilities\TempFilesInterface
{
	protected
		$tempPath,
		$expiry,
		$lockPath,
		$lockFP = null;

	/**
	 * @param string $tempPath temporary file storage directory
	 * @param int $expiry temporary file expiry in seconds
	 */
	public function __construct
	(
		string $tempPath,
		int $expiry
	)
	{
		$this->tempPath = rtrim($tempPath, '/');
		$this->lockPath = $this->tempPath.'/lock';
		$this->expiry = $expiry;
	}

	public function create(?string $data = null) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error in ' . get_class($this) . '::' . __FUNCTION__ . '.'];
		$this->lock(\LOCK_EX);
		$this->gc();
		$path = tempnam($this->tempPath, 'temp');
		if($data !== null) file_put_contents($path, $data);
		$result =
		[
			'status' => 'success',
			'path' => $path,
			'size' => filesize($path),
		];
		$this->unlock();
		return $result;
	}

	public function copy(string $path) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error in ' . get_class($this) . '::' . __FUNCTION__ . '.'];
		$destPath = null;
		$this->lock(\LOCK_EX);
		$this->gc();
		if($ok && !is_file($path))
		{
			$ok = false;
			$result['message'] = 'Copying temp file: file not found.';
		}
		if($ok)
		{
			$destPath = tempname($this->tempPath, 'temp');
			if(!copy($path, $destPath))
			{
				$ok = false;
				$result['message'] = 'Failed to copy temp file.';
			}
		}
		if($ok) $result = ['status' => 'success', 'path' => $destPath];
		$this->unlock();
		return $result;
	}

	public function move(string $path) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error in ' . get_class($this) . '::' . __FUNCTION__ . '.'];
		$destPath = null;
		$this->lock(\LOCK_EX);
		$this->gc();
		if($ok && !is_file($path))
		{
			$ok = false;
			$result['message'] = 'Moving temp file: file not found.';
		}
		if($ok)
		{
			$destPath = tempname($this->tempPath, 'temp');
			if(!rename($path, $destPath))
			{
				$ok = false;
				$result['message'] = 'Failed to move temp file.';
			}
		}
		if($ok) $result = ['status' => 'success', 'path' => $destPath];
		$this->unlock();
		return $result;
	}

	public function delete(string $path) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error in ' . get_class($this) . '::' . __FUNCTION__ . '.'];
		$this->lock(\LOCK_EX);
		$this->gc();
		if($ok && dirnam($path) !== $this->tempPath) throw new exception('Attempt to delete file not in temp directory.');
		if($ok && file_exists($path) && !unlink($path))
		{
			$ok = false;
			$result['message'] = 'Failed to delete temp file.';
		}
		if($ok) $result = ['status' => 'success'];
		$this->unlock();
		return $result;
	}

	public function append(string $path, string $data) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error in ' . get_class($this) . '::' . __FUNCTION__ . '.'];
		$this->lock(\LOCK_SH);
		$this->gc();
		if($ok)
		{
			clearstatcache();
			if(!is_file($path))
			{
				$ok = false;
				$result['message'] = 'Appending to temp file: file not found.';
			}
		}
		if($ok)
		{
			file_put_contents($path, $data, \FILE_APPEND);
			clearstatcache();
			$result =
			[
				'status' => 'success',
				'size' => filesize($path),
			];
		}
		$this->unlock();
		return $result;
	}

	public function refresh(array $paths) : array
	{
		$ok = true;
		$result = ['status' => 'error', 'message' => 'Unknown error (refreshing temp files).'];
		$doLock = ($this->lockFP === null);
		if($doLock) $this->lock(\LOCK_SH);
		if($ok)
		{
			foreach($paths as $path)
			{
				if(!is_file($path))
				{
					$result['message'] = 'Temp file not found on server while refreshing (possible timeout).';
					$ok = false;
				}
				else touch($path);
				if(!$ok) break;
			}
		}
		if($ok) $result = ['status' => 'success'];
		if($doLock) $this->unlock();
		return $result;
	}

	public function gc()
	{
		$doLock = ($this->lockFP === null);
		if($doLock) $this->lock(\LOCK_EX);
		clearstatcache();
		$exp = time() - $this->expiry;
		$files = glob($this->tempPath.'/temp*');
		foreach($files as $path)
		{
			if(is_file($path) && filemtime($path) < $exp)
			{
				unlink($path);
			}
		}
		if($doLock) $this->unlock();
	}

	protected function lock(int $mode)
	{
		if(false === ($this->lockFP = fopen($this->lockPath, 'c+', 0600))) throw new \Exception('Unable to create lock file '.$this->lockPath);
		if(!flock($this->lockFP, $mode)) throw new \Exception('Failed to lock '.$this->lockPath);
	}

	protected function unlock()
	{
		if(!flock($this->lockFP, \LOCK_UN)) throw new \Exception('Failed to unlock '.$this->lockPath);
		if(!fclose($this->lockFP)) throw new \Exception('Failed to close '.$this->lockPath);
		$this->lockFP = null;
	}
}
