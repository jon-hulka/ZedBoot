<?php
/**
 * Interface RandomText | ZedBoot/Utilities/RandomText.class.php
 * @license     GNU General Public License, version 3
 * @package     Utilities
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2020 - 2021 Jonathan Hulka
 */
namespace ZedBoot\Utilities;
class RandomText implements RandomTextInterface
{
	protected
		$allowedChars,
		//Maximum index to use
		$allowedCount,
		//Index values will be taken from a pool of this size
		//It is a power of 2 to keep the distribution of values even
		$distributionSize,
		//This many characters will be generated for each output character
		$ratio;

	/**
	 * @param string $allowedChars characters to choose from
	 * @param string|null $allowedInitial optional characters to choose first character from
	 * @return void
	 */
	public function __construct(string $allowedChars, string $allowedInitial = null)
	{
		$this->allowedChars = $allowedChars;
		$this->allowedInitial = $allowedInitial;
		$this->allowedCount = strlen($allowedChars);
		if($this->allowedCount < 1 || $this->allowedCount > 256) throw new \Exception('At least 1 and no more than 256 allowed character(s) must be specified.');
		if($allowedInitial !== null)
		{
			$len = strlen($allowedInitial);
			if($len < 1 || $len > 256) throw new \Exception('At least 1 and no more than 256 allowed initial(s) must be specified.');
		}
		//Find the power of 2 equal or greater than the number of allowed characters
		$this->distributionSize = pow(2,ceil(log($this->allowedCount, 2)));
		//Generating random bytes is the expensive part of this algorithm
		//In most cases some will be wasted so it is helpful to produce some extras, but not too many
		//On average, this is how many characters needed to produce 1 character in the allowed set
		//50% of the time, more characters will be needed. My tests have shown this to perform well.
		$this->ratio = $this->distributionSize / $this->allowedCount;
	}

	public function get(int $length) : string
	{
		if($length < 1) throw new \Exception('$length must be >= 1.');
		$result = '';
		//Keep track of result length to prevent having to compute strlen()
		$l = 0;
		$indices = null;
		$i = null;
		do
		{
			//Bytes will be used to index the character set. Convert to integers.
			$indices = unpack('C*', random_bytes(ceil(($length - $l) * $this->ratio)));
			foreach($indices as $i)
			{
				if($l === 0 && $this->allowedInitial !== null)
				{
					//Reduce to the smallest range that gives an even distribution
					$i %= pow(2, ceil(log(strlen($this->allowedInitial), 2)));
					//If the index is within the range of characters, add one char to the string
					if($i < strlen($this->allowedInitial))
					{
						$l++;
						$result .= $this->allowedInitial[$i];
					}
				}
				else
				{
					//Reduce to the smallest range that gives an even distribution
					$i %= $this->distributionSize;
					//If the index is within the range of characters, add one char to the string
					if($i < $this->allowedCount)
					{
						$l++;
						$result .= $this->allowedChars[$i];
					}
				}
				if($l >= $length) break;
			}
		}while($l < $length);
		return $result;
	}
}
