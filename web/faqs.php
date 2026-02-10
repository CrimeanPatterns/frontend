<?php
// -----------------------------------------------------------------------
// FAQs
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------
require "kernel/public.php";

require "$sPath/lib/classes/TBaseForumList.php";

// select menus
require $sPath . "/design/topMenu/main.php";

if (NDInterface::enabled()) {
    ?>
	<script type="text/javascript">
        document.location.href = '<?php echo getSymfonyContainer()->get("router")->generate("aw_faq_index"); ?>' + document.location.hash;
	</script>
    <?php
    exit;
}

$metaDescription = "What is AwardWallet? What are the benefits? Why should I track my award balances?";
$sTitle = "Why should I track my award balances and travel plans?";
$pageTitle = $sTitle;
$bSecuredPage = false;

require "$sPath/design/header.php";

$cats = getSymfonyContainer()->get('doctrine')->getManager()
    ->getRepository(\AwardWallet\MainBundle\Entity\Faqcategory::class)
    ->findBy([
        'visible' => true,
    ], ['rank' => 'ASC']
    );
$translator = getSymfonyContainer()->get("aw.extension.translation.entity");

?>
<div style='margin-left: 23px;'>
    <?php
    foreach ($cats as $cat) {
        ?>
		<div class="boxToll boxBlue boxFaq qLess">
			<div class="top">
				<div class="left"></div>
			</div>
			<div class="center">
				<div class="centerInner">
                    <div class="text"><?php echo $translator->trans(/** @Ignore */ $cat, 'categorytitle'); ?></div>
				</div>
			</div>
			<div class="bottom">
				<div class="left"></div>
			</div>
		</div>
		<div class="boxToll pageNote afterBlue boxFaq qLess" style="margin-bottom: 23px;">
			<div class="top">
				<div class="left"></div>
				<div class="downArrow"></div>
			</div>
			<div class="center pad">
				<div>
					<div class="text">
                        <?php
                        foreach ($cat->getVisibleFaqs() as $faq) {
                            ?>
							<div class="row">
								<div class="icon"></div>
                                <div class="text"><a
                                        href="#<?php echo $faq->getFaqid(); ?>"><?php echo $translator->trans(/** @Ignore */ $faq, 'question'); ?></a>
                                </div>
							</div>
                            <?php
                        }
        ?>
					</div>
				</div>
			</div>
			<div class="bottom">
				<div class="left"></div>
			</div>
		</div>
        <?php
    }

    foreach ($cats as $cat) {
        ?>
        <div class="redSubHeader"
             style="padding-left: 23px; margin-top: 20px; margin-bottom: 13px;"><?php echo $translator->trans(/** @Ignore */ $cat, 'categorytitle'); ?></div>
        <?php
        foreach ($cat->getVisibleFaqs() as $faq) {
            ?>
            <a name="<?php echo $faq->getFaqid(); ?>"></a>
			<div class="boxToll boxBlue boxFaq">
				<div class="top">
					<div class="left"></div>
				</div>
				<div class="center">
					<div class="centerInner">
						<div class="q">Q:</div>
                        <div class="text"><a
                                href="#<?php echo $faq->getFaqid(); ?>"><?php echo $translator->trans(/** @Ignore */ $faq, 'question'); ?></a>
                        </div>
					</div>
				</div>
				<div class="bottom">
					<div class="left"></div>
				</div>
			</div>
			<div class="boxToll pageNote afterBlue boxFaq" style="margin-bottom: 23px;">
				<div class="left"></div>
				<div class="downArrow"></div>
				<div class="center pad">
					<div class="centerInner">
						<div class="q">A:</div>
                        <div class="text"><?php echo $translator->trans(/** @Ignore */ $faq, 'answer'); ?></div>
					</div>
				</div>
				<div class="bottom">
					<div class="left"></div>
				</div>
			</div>
            <?php
        }
    }
?>
</div>
<?php
require "$sPath/design/oneCardPopup.php";

require "$sPath/design/footer.php";
?>
