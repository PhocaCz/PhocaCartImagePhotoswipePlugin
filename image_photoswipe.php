<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;
jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.html.parameter' );


if (file_exists(JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/bootstrap.php')) {
	// Joomla 5 and newer
	require_once(JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/bootstrap.php');
} else {
	// Joomla 4
	JLoader::registerPrefix('Phocacart', JPATH_ADMINISTRATOR . '/components/com_phocacart/libraries/phocacart');
}

final class plgPCVImage_Photoswipe extends CMSPlugin
{
	private ?\Joomla\CMS\Application\CMSApplicationInterface $app = null;
	private $doc = null;


	function __construct(& $subject, $config) {
		parent :: __construct($subject, $config);

		$this->loadLanguage();
	}


	public function onPCVonItemImage($context, $item, $t, $params) {


		$zoom_image = $this->params->get('zoom_image', 'l');
	    $layoutI	= new FileLayout('image', null, array('component' => 'com_phocacart'));

		$this->app	= Factory::getApplication();
	    $this->doc	= $this->app->getDocument();
		$wa = $this->doc->getWebAssetManager();
		$wa->registerAndUseStyle('plg_pcv_image_photoswipe.photoswipe', 'media/plg_pcv_image_photoswipe/css/photoswipe.css', array('version' => 'auto'));
		$wa->registerAndUseStyle('plg_pcv_image_photoswipe.photoswipe.default', 'media/plg_pcv_image_photoswipe/css/default-skin/default-skin.css', array('version' => 'auto'));
		$wa->registerAndUseStyle('plg_pcv_image_photoswipe.photoswipe.style', 'media/plg_pcv_image_photoswipe/css/photoswipe-style.css', array('version' => 'auto'));


	    $s      = PhocacartRenderStyle::getStyles();
        $o      = array();
        $x      = $item;
        $idName	= 'VItemP'.(int)$x->id;

        $label = PhocacartRenderFront::getLabel($x->date, $x->sales, $x->featured);

		// IMAGE
		$image = PhocacartImage::getThumbnailName($t['pathitem'], $x->image, 'large');// Image
		$imageL = PhocacartImage::getThumbnailName($t['pathitem'], $x->image, 'large');// Image Link to enlarge

		// Some of the attribute is selected - this attribute include image so the image should be displayed instead of default
		$imageA = PhocaCartImage::getImageChangedByAttributes($t['attr_options'], 'large');
		if ($imageA != '') {
			$image = PhocacartImage::getThumbnailName($t['pathitem'], $imageA, 'large');
			$imageL = PhocacartImage::getThumbnailName($t['pathitem'], $imageA, 'large');
		}

		$link = Uri::base(true) . '/' . $imageL->rel;// Thumbnail - Large Thumbnail as default
		$linkAbs = $imageL->abs;

		if ($t['display_webp_images'] == 1) {
			$link = Uri::base(true) . '/' . $imageL->rel_webp;
			$linkAbs = $imageL->abs_webp;
		}

		$linkO = '';
		if ($zoom_image == 'o') {
		    $linkO = Uri::base(true) . '/' . $t['pathitem']['orig_rel_ds'] . $x->image;// Original image
			$linkOAbs = $t['pathitem']['orig_abs_ds'] . $x->image;// Original image;
       	}

		$o[] = '<div class="pc-photoswipe">';

		if (isset($image->rel) && $image->rel != '') {

			$altValue = PhocaCartImage::getAltTitle($x->title, $image->rel);

			$o[] = '<div class="ph-item-image-full-box ' . $label['cssthumbnail'] . '">';
			$o[] = '<div class="ph-label-box">';
			$o[] = $label['new'] . $label['hot'] . $label['feat'];
			if ($t['taglabels_output'] != '') {
				$o[] = $t['taglabels_output'];
			}
			$o[] = '</div>';

			$o[] ='<figure>';

			if (isset($t['image_width']) && (int)$t['image_width'] > 0 && isset($t['image_height']) && (int)$t['image_height'] > 0) {
				$dataSize = 'data-size=' . $t['image_width'] . 'x' . $t['image_height'] . '';
			} else {
				if ($linkO != ''){
					list($w, $h, $type) = GetImageSize($linkOAbs);
				} else {
					list($w, $h, $type) = GetImageSize($linkAbs);
				}
				$dataSize = 'data-size=' . $w . 'x' . $h . '';
			}

			$o[] = '<a href="' . ($linkO != '' ? $linkO : $link) . '" class="' . $t['image_class'] . ' phjProductHref' . $idName . ' phImageFullHref phImageGalleryHref pc-photoswipe-button" data-href="' . $link . '" data-img-title="'.$x->title.'" id="pgImg'.$x->id.'" itemprop="contentUrl" '.$dataSize.'>';


			$d = array();
			$d['t'] = $t;
			$d['s'] = $s;
			$d['src'] = Uri::base(true) . '/' . $image->rel;
			$d['srcset-webp'] = Uri::base(true) . '/' . $image->rel_webp;
			$d['data-image'] = Uri::base(true) . '/' . $image->rel;
			$d['data-image-webp'] = Uri::base(true) . '/' . $image->rel_webp;
			$d['alt-value'] = PhocaCartImage::getAltTitle($x->title, $image->rel);
			$d['data-image-large'] = $link;
			$d['data-image-original'] = $linkO;
			$d['class'] = PhocacartRenderFront::completeClass(array($s['c']['img-responsive'], $label['cssthumbnail2'], 'ph-image-full', 'phImageFull', 'phImageGallery', 'phjProductImage' . $idName));
			$d['style'] = '';
			if (isset($t['image_width']) && (int)$t['image_width'] > 0 && isset($t['image_height']) && (int)$t['image_height'] > 0) {
				$d['style'] = 'width:' . $t['image_width'] . 'px;height:' . $t['image_height'] . 'px';
			}
			$o[] = $layoutI->render($d);

			$o[] = '</a>';

			//$o[] = '<figcaption itemprop="caption description"></figcaption>';
			$o[] = '</figure>';

			$o[] = '</div>';// end item_row_item_box_full_image
		}

		// ADDITIONAL IMAGES
		if (!empty($t['add_images'])) {

			$o[] = '<div class="' . $s['c']['row'] . ' ph-item-image-add-box">';

			$i = 1;


			foreach ($t['add_images'] as $v2) {

				if ($v2->image == '') {
					continue;
				}

				$active = '';

				$o[] = '<div class="' . $s['c']['col.xs12.sm4.md4'] . ' ph-item-image-box">';

				$image = PhocacartImage::getThumbnailName($t['pathitem'], $v2->image, 'small');
				$imageL = PhocacartImage::getThumbnailName($t['pathitem'], $v2->image, 'large');

				$link = Uri::base(true) . '/' . $imageL->rel;// Thumbnail - Large Thumbnail as default
				$linkAbs = $imageL->abs;

				if ($t['display_webp_images'] == 1) {
					$link = Uri::base(true) . '/' . $imageL->rel_webp;
					$linkAbs = $imageL->abs_webp;
				}


				$linkO = '';
				if ($zoom_image == 'o') {
					$linkO = Uri::base(true) . '/' . $t['pathitem']['orig_rel_ds'] . $v2->image;// Original image
					$linkOAbs = $t['pathitem']['orig_abs_ds'] . $v2->image;// Original image;
				}

				$altValue = PhocaCartImage::getAltTitle($x->title, $v2->image);

				$o[] ='<figure>';

				if (isset($t['image_width']) && (int)$t['image_width'] > 0 && isset($t['image_height']) && (int)$t['image_height'] > 0) {
					$dataSize = 'data-size=' . $t['image_width'] . 'x' . $t['image_height'] . '';
				} else {
					if ($linkO != ''){
						list($w, $h, $type) = GetImageSize($linkOAbs);
					} else {
						list($w, $h, $type) = GetImageSize($linkAbs);
					}
					$dataSize = 'data-size=' . $w . 'x' . $h . '';
				}

				$o[] = '<a href="' . ($linkO != '' ? $linkO : $link) . '" class="' . $t['image_class'] . ' phjProductHref' . $idName . ' phImageFullHref phImageAdditionalHref pc-photoswipe-button" data-href="' . $link . '" data-img-title="'.$x->title.'" id="pgImgAdd'.$x->id.'Add'.$i.'" itemprop="contentUrl" '.$dataSize.'>';


				$d = array();
				$d['t'] = $t;
				$d['s'] = $s;
				$d['src'] = Uri::base(true) . '/' . $image->rel;
				$d['srcset-webp'] = Uri::base(true) . '/' . $image->rel_webp;
				$d['alt-value'] = PhocaCartImage::getAltTitle($x->title, $v2->image);
				$d['data-image-large'] = $link;
				$d['data-image-original'] = $linkO;
				$d['class'] = PhocacartRenderFront::completeClass(array($s['c']['img-responsive'], $label['cssthumbnail2'], 'ph-image-full', 'phImageAdditional', $active /*, 'phjProductImage'.$idName*/));
				$o[] = $layoutI->render($d);

				$o[] = '</a>';

				$o[] = '</figure>';

				$o[] = '</div>';
				$i++;
			}

			$o[] = '</div>';// end additional images


		}

		$o[] = '</div >';// end photoswipe

		$o[] = $this->loadPhotoswipeBottomPlugin();

        return trim(implode("\n", $o));
    }

	function onPCVonItemImageBeforeLoadingImageLibrary(&$pluginData, $eventData) {

		// Disable all other popup windows methods (e.g. not load prettyphoto JS or CSS)
		$pluginData['image_popup_method'] = 0;
		return true;

	}

	public function loadPhotoswipeBottomPlugin($forceSlideshow = 0, $forceSlideEffect = 0) {

		$photoswipe_slideshow	= $this->params->get( 'photoswipe_slideshow', 1 );
		$photoswipe_slide_effect= $this->params->get( 'photoswipe_slide_effect', 0 );

		if ($forceSlideshow == 1) {
            $photoswipe_slideshow = 1;
        }
		if ($forceSlideEffect == 1) {
		    $photoswipe_slide_effect = 1;
        }

		$o = '<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

    <!-- Background of PhotoSwipe.
         It\'s a separate element, as animating opacity is faster than rgba(). -->
    <div class="pswp__bg"></div>

    <!-- Slides wrapper with overflow:hidden. -->
    <div class="pswp__scroll-wrap">

        <!-- Container that holds slides. PhotoSwipe keeps only 3 slides in DOM to save memory. -->
        <!-- don\'t modify these 3 pswp__item elements, data is added later on. -->
        <div class="pswp__container">
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>

        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
        <div class="pswp__ui pswp__ui--hidden">

            <div class="pswp__top-bar">

                <!--  Controls are self-explanatory. Order can be changed. -->

                <div class="pswp__counter"></div>

                <button class="pswp__button pswp__button--close" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_CLOSE').'"></button>

                <button class="pswp__button pswp__button--share" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_SHARE').'"></button>

                <button class="pswp__button pswp__button--fs" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_TOGGLE_FULLSCREEN').'"></button>

                <button class="pswp__button pswp__button--zoom" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_YES_ZOOM_IN_OUT').'"></button>';

				if ($photoswipe_slideshow == 1) {
					$o .= '<!-- custom slideshow button: -->
					<button class="pswp__button pswp__button--playpause" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_PLAY_SLIDESHOW').'"></button>
					<span id="phTxtPlaySlideshow" style="display:none">'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_PLAY_SLIDESHOW').'</span>
					<span id="phTxtPauseSlideshow" style="display:none">'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_PAUSE_SLIDESHOW').'</span>';
				}

                $o .= '<!-- Preloader -->
                <!-- element will get class pswp__preloader--active when preloader is running -->
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                      <div class="pswp__preloader__cut">
                        <div class="pswp__preloader__donut"></div>
                      </div>
                    </div>
                </div>
            </div>

            <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                <div class="pswp__share-tooltip"></div> 
            </div>

            <button class="pswp__button pswp__button--arrow--left" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_PREVIOUS').'">
            </button>

            <button class="pswp__button pswp__button--arrow--right" title="'.Text::_('PLG_PCV_IMAGE_PHOTOSWIPE_NEXT').'">
            </button>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>

          </div>

        </div>

</div>';

		$wa = $this->doc->getWebAssetManager();
		$wa->registerAndUseScript('plg_pcv_image_photoswipe.photoswipe', 'media/plg_pcv_image_photoswipe/js/photoswipe.min.js', array('version' => 'auto'), ['defer' => true]);
		$wa->registerAndUseScript('plg_pcv_image_photoswipe.photoswipe.default', 'media/plg_pcv_image_photoswipe/js/photoswipe-ui-default.min.js', array('version' => 'auto'), ['defer' => true]);

		if ($photoswipe_slide_effect == 1) {
			$wa->registerAndUseScript('plg_pcv_image_photoswipe.photoswipe.initialize.ratio', 'media/plg_pcv_image_photoswipe/js/photoswipe-initialize-ratio.js', array('version' => 'auto'), ['defer' => true]);
		} else {
			$wa->registerAndUseScript('plg_pcv_image_photoswipe.photoswipe.initialize.ratio', 'media/plg_pcv_image_photoswipe/js/photoswipe-initialize.js', array('version' => 'auto'), ['defer' => true]);
		}

		return $o;
	}
}
?>
