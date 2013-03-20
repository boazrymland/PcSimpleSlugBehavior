<?php
/**
 * PcSimpleSlugBehavior.php
 * Created on 26 06 2012 (11:09 AM)
 *
 * @license:
 * Copyright (c) 2012, Boaz Rymland
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 *      disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
 *      disclaimer in the documentation and/or other materials provided with the distribution.
 * - The names of the contributors may not be used to endorse or promote products derived from this software without
 *      specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
class PcSimpleSlugBehavior extends CActiveRecordBehavior {
	/**
	 * @var string the attribute that contains the 'main string' that is used when building the slug
	 */
	public $sourceStringAttr = "title";

	/**
	 * @var string the attribute that contains the 'main string' that is used when building the slug
	 */
	public $sourceStringPrepareMethod = false;

	/**
	 * @var string the attribute/column name that holds the Id/primary-key for this model.
	 */
	public $sourceIdAttr = 'id';

	/**
	 * @var bool Supports avoiding prefixing the 'id' attribute of the model in the beginning of the slug. Use
	 * with caution(!) as this is tricky can can lead to two record with the same slug (consider carefully
	 * your requirements before setting this to true).
	 */
	public $avoidIdPrefixing = false;

	/**
	 * @var int maximum allowed slug length. slug will be crudely trimmed to this length if longer than it.
	 */
	public $maxChars = 100;

	/**
	 * @var bool whether to lowercase the resulted URLs or not. default = yes.
	 */
	public $lowercaseUrl = true;
	/**
	 * @var string the slug.
	 */
	private $slug;

	/**
	 * @return string: the prepared slug for 'this->owner' model object
	 * @throws CException
	 */
	public function generateUniqueSlug() {
		if (!$this->avoidIdPrefixing) {
			// check that the defined 'id attribute' exists for 'this' model. explode if not.
			if (!$this->owner->hasAttribute($this->sourceIdAttr)) {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceIdAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please help!"
				);
			}
		}
		// if we're supposed to get the slug raw material from a method, check it exists and if so run it.
		if ($this->sourceStringPrepareMethod) {
			if (method_exists($this->owner, $this->sourceStringPrepareMethod)) {
				$this->slug = $this->createBaseSlug($this->owner->{$this->sourceStringPrepareMethod}());
			}
			else {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have the method that was supposed to return the string for the slug (method name={$this->sourceStringPrepareMethod})." .
						" Don't know how to continue. Please fix it!"
				);
			}

		}
		// no preparation method - check that the defined 'source string attribute' exists for 'this' model. explode if not.
		else {
			if (!$this->owner->hasAttribute($this->sourceStringAttr)) {
				throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceStringAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please fix it!"
				);
			}
			// create the base slug out of this attribute:
			// convert all spaces to underscores:
			$this->slug = $this->createBaseSlug($this->owner->{$this->sourceStringAttr});
		}

		// prepend everything with the id of the model followed by a dash
		if (!$this->avoidIdPrefixing) {
			$id_attr = $this->sourceIdAttr;
			$this->slug = $this->owner->$id_attr . "-" . $this->slug;
		}

		// trim if necessary:
		if (mb_strlen($this->slug) > $this->maxChars) {
			$this->slug = mb_substr($this->slug, 0, $this->maxChars);
		}

		// lowercase url if needed to
		if ($this->lowercaseUrl) {
			$this->slug = mb_strtolower($this->slug, 'UTF-8');
		}

		// done
		return $this->slug;
	}

	/**
	 * Returns 'treated' string with special characters stripped off of it, spaces turned to dashes. It serves as a 'base
	 * slug' that can be further treated (and used internally by generateUniqueSlug()).
	 *
	 * It is useful when you need to add misc parameters to URLs and want them 'treated' (as 'treated' is performed here)
	 * but those string are irrelevant to Id of a model etc. E.g.: Create a URL in the format of "/.../<city-name>/..."
	 * - the city-name parameter was required to be 'treated' before applying to URL.
	 *
	 * @param string $str source string
	 * @return string resulted string after manipulation.
	 */
	public function createBaseSlug($str) {
		// convert all spaces to underscores:
		$treated = strtr($str, " ", "_");
		// convert what's needed to convert to nothing (remove them...)
		$treated = preg_replace('/[\!\@\#\$\%\^\&\*\(\)\+\=\~\:\.\,\;\'\"\<\>\/\\\`]/', "", $treated);
		// convert underscores to dashes
		$treated = strtr($treated, "_", "-");

		if ($this->lowercaseUrl) {
			$treated = mb_strtolower($treated, 'UTF-8');
		}

		return $treated;
	}

	/**
	 * Returns the Id (=primary key) from a given slug
	 *
	 * @param string $slug
	 * @return int
	 */
	public function getIdFromSlug($slug) {
		$parts = explode("-", $slug);
		return $parts[0];
	}
}
