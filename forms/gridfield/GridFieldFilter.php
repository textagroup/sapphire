<?php
/**
 * GridFieldFilter alters the gridfield with some filtering fields in the header of each column
 * 
 * @see GridField
 * 
 * @package sapphire
 * @subpackage fields-relational
 */
class GridFieldFilter implements GridField_HTMLProvider, GridField_DataManipulator, GridField_ActionProvider {
	
	/**
	 *
	 * @param GridField $gridField
	 * @return array
	 */
	public function getActions($gridField) {
		return array('filter', 'reset');
	}

	function handleAction(GridField $gridField, $actionName, $arguments, $data) {
		$state = $gridField->State->GridFieldFilter;
		if($actionName === 'filter') {
			if(isset($data['filter'])){
				foreach($data['filter'] as $key => $filter ){
					$state->Columns->$key = $filter;
				}
			}
		} elseif($actionName === 'reset') {
			$state->Columns = null;
		}
	}


	/**
	 *
	 * @param GridField $gridField
	 * @param SS_List $dataList
	 * @return SS_List 
	 */
	public function getManipulatedData(GridField $gridField, SS_List $dataList) {
		$state = $gridField->State->GridFieldFilter;
		if(!isset($state->Columns)) {
			return $dataList;
		}
		
		$filterArguments = $state->Columns->toArray();
		foreach($filterArguments as $columnName => $value ) {
			if($dataList->canFilterBy($columnName) && $value) {
				$dataList->filter($columnName.':PartialMatch', $value);
			}
		}
		return $dataList;
	}

	public function getHTMLFragments($gridField) {
		$forTemplate = new ArrayData(array());
		$forTemplate->Fields = new ArrayList;

		$columns = $gridField->getColumns();
		$filterArguments = $gridField->State->GridFieldFilter->Columns->toArray();
		
		$currentColumn = 0;
		foreach($columns as $columnField) {
			$currentColumn++;
			$metadata = $gridField->getColumnMetadata($columnField);
			$title = $metadata['title'];
			if($title && $gridField->getList()->canFilterBy($columnField)) {
				$value = '';
				if(isset($filterArguments[$columnField])) {
					$value = $filterArguments[$columnField];
				}
				$field = new TextField('filter['.$columnField.']', '', $value);
				$field->addExtraClass('ss-gridfield-sort');

				$field = new FieldGroup(
					$field,
					$filterbutton = new GridField_Action($gridField, 'filter', _t('GridField.Filter', "Filter"), 'filter', null),
					$resetbutton = new GridField_Action($gridField, 'reset', _t('GridField.ResetFilter', "Reset"), 'reset', null)
				);

				$filterbutton->addExtraClass('ss-gridfield-button-filter');
				$resetbutton->addExtraClass('ss-gridfield-button-reset');	
			} else {
				$field = new LiteralField('', '');
			}

			$field->iteratorProperties($currentColumn-1, count($columns));
			$forTemplate->Fields->push($field);
		}
		
		return array(
			'header' => $forTemplate->renderWith('GridFieldFilter_Row'),
		);
	}
}