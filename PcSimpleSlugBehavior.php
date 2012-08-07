<?php
/**
 * PcSimpleSlugBehavior.php
 * Created on 26 06 2012 (11:09 AM)
 *
 */
class PcSimpleSlugBehavior extends CActiveRecordBehavior {
	/*
	 * @var string the attribute that contains the 'main string' that is used when building the slug
	 */
	public $sourceStringAttr = "title";

	/*
	 * @var string the attribute/column name that holds the Id/primary-key for this model.
	 */
	public $sourceIdAttr = 'id';

	/**
	 * @var int maximum allowed slug length. slug will be crudely trimmed to this length if longer than it.
	 */
	public $maxChars = 100;

	/**
	 * @var bool whether to lowercase the resulted URLs or not. default = yes.
	 */
	public $lowercaseUrl = true;

	/**
	 * @return string: the prepared slug for 'this->owner' model object
	 * @throws CException
	 */
	public function generateUniqueSlug() {
		// check that the defined 'source string attribute' exists for 'this' model. explode if not.
		if (!$this->owner->hasAttribute($this->sourceStringAttr)) {
			throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceStringAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please fix it!"
			);
		}
		// check that the defined 'id attribute' exists for 'this' model. explode if not.
		if (!$this->owner->hasAttribute($this->sourceIdAttr))  {
			throw new CException ("requested to prepare a slug for " .
						get_class($this->owner) .
						" (id=" . $this->owner->getPrimaryKey() .
						") but this model doesn't have an attribute named " . $this->sourceIdAttr .
						" from which I'm supposed to create the slug. Don't know how to continue. Please help!"
			);
		}

		// all passed. do the magic:
		$text_attr = $this->sourceStringAttr;
		// convert all spaces to underscores:
		$slug = $this->createBaseSlug($this->owner->$text_attr);

		// prepend everything with the id of the model followed by a dash
		$id_attr = $this->sourceIdAttr;
		$slug = $this->owner->$id_attr . "-" . $slug;

		// trim if necessary:
		if (mb_strlen($slug) > $this->maxChars) {
			$slug = mb_substr($slug, 0, $this->maxChars);
		}

		// lowercase url if needed to
		if ($this->lowercaseUrl) {
			$slug = mb_strtolower($slug, 'UTF-8');
		}

		// done
		return $slug;
	}

	/**
	 * Returns 'treated' string with special characters stripped off of it, spaces turned to dashes. It serves as a 'base
	 * slug' that can be further treated (and used internally by generateUniqueSlug()).
	 * It is useful when you need to add misc paramters to URLs and want them 'treated' (as 'treated' is performed here)
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
