<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Blog
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE   = 'blog';

    public function getElement($variantIndex, $index)
    {
        $element = false;
        $choiceId = $this->getHelper()->getVariantChoiceId($variantIndex);
        $ids = $this->getHelper()->getHitFieldValues('id', $this->getType(), $choiceId);
        foreach ($ids as $i => $id) {
            $ids[$i] = str_replace('blog_', '', $id);
        }
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->getResourceManager()->getResource($variantIndex, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $element) {
                    if($element['id'] == $entity_id){
                        return $element;
                    }
                }
            }

            $element = $this->getResourceManager()->getResource($entity_id, 'blog');
            if(is_null($element)) {
                $articles = $this->dataHelper->enhanceBlogArticles($this->getHelper()->getBlogs([$entity_id]));
                $element = reset($articles);
                $this->getResourceManager()->setResource($element, $entity_id, 'blog');
            }
        }

        return $element;
    }

}
