<?php

/**
 * Description of AdAdmin
 *
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD http://silverstripe.org/BSD-license
 */
class AdAdmin extends ModelAdmin {
	public static $managed_models = array(
		'Advertisement',
		'AdCampaign',
		'AdClient',
	);

	static $allowed_actions = array(
		'preview'
	);

	static $url_rule = '/$Action/$ID/$OtherID';
	
	public static $url_segment = 'advertisements';
	public static $menu_title = 'Ads';
	public static $collection_controller_class = "AdAdmin_Controller";

	/** Preview an advertisement.
	 */
	public function preview(SS_HTTPRequest $request)
	{
		$request->shift();
		$adID = (int) $request->param('ID');
		$ad = DataObject::get_by_id('Advertisement', $adID);

		if(!$ad)
		{
			Controller::curr()->httpError(404);
			return;
		}

		// No impression and click tracking for previews
		Advertisement::$use_js_tracking = false;

		// Block stylesheets and JS that are not required (using our own template)
		Requirements::clear();

		$template = new SSViewer('AdPreviewPage');

		return $template->Process($ad);
	}
}

class AdAdmin_Controller extends ModelAdmin_CollectionController {
	function getResultsTable($searchCriteria) {
		$summaryFields = $this->getResultColumns($searchCriteria);

		if ($this->modelClass == 'Advertisement') {
			$summaryFields = array(
				'Title' => 'Title',
				'Clicks' => 'Clicks',
				'Impressions' => 'Impressions',
			);
		}
		
		$className = $this->parentController->resultsTableClassName();
		$tf = new $className(
			$this->modelClass,
			$this->modelClass,
			$summaryFields
		);

		$tf->setCustomQuery($this->getSearchQuery($searchCriteria));
		$tf->setPageSize($this->parentController->stat('page_length'));
		$tf->setShowPagination(true);
		// @todo Remove records that can't be viewed by the current user
		$tf->setPermissions(array_merge(array('view','export'), TableListField::permissions_for_object($this->modelClass)));

		// csv export settings (select all columns regardless of user checkbox settings in 'ResultsAssembly')
		$exportFields = $this->getResultColumns($searchCriteria, false);
		$tf->setFieldListCsv($exportFields);

		$url = '<a href=\"' . $this->Link() . '/$ID/edit\">$value</a>';
		$tf->setFieldFormatting(array_combine(array_keys($summaryFields), array_fill(0,count($summaryFields), $url)));
	
		return $tf;
	}
}

