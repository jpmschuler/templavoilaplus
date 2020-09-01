<?php

namespace \Ppi\TemplaVoilaPlus\Ext\Backend\Form;


/**
 * Q3i - add node type for thumbnails, taken from 9.5
 * Probably we could achieve this with registering formEngine|nodeRegistry in localconf, but i'd rather keep all this
 * here in classes to not complicate this more than it already is.
 *
 * If needed for anything, other nodes like available file ext list or file upload button can be probably
 * added the same way. I don't need this here now, so I'm not wasting any more time on this topic. Basically works.
 */
class NodeFactory extends \TYPO3\CMS\Backend\Form\NodeFactory {

   
    /**
     * Add node type for thumbnail for tca group/file compatibility
     */
    protected function registerAdditionalNodeTypesFromConfiguration()
    {
    	// q3i
    	parent::registerAdditionalNodeTypesFromConfiguration();
        $this->nodeTypes['fileThumbnails'] = \Ppi\TemplaVoilaPlus\Ext\Backend\Form\FieldWizard\FileThumbnails::class;
    }

}
