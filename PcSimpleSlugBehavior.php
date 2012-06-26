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
		$slug = strtr($this->owner->$text_attr, " ", "_");
		// convert what's needed to convert to nothing (remove them...)
		$slug = preg_replace('/[\!\@\#\$\%\^\&\*\(\)\+\=\~\:\.\,\;\'\"\<\>\/\\\`]/', "", $slug);
		// convert underscores to dashes
		$slug = strtr($slug, "_", "-");

		// prepend everything with the id of the model followed by a dash
		$id_attr = $this->sourceIdAttr;
		$slug = $this->owner->$id_attr . "-" . $slug;

		// done
		return $slug;
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
