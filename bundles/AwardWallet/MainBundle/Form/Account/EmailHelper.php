<?php

namespace AwardWallet\MainBundle\Form\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\DynamicUtils;
use AwardWallet\MainBundle\Loyalty\AutologinLinkValidator;
use AwardWallet\MainBundle\Service\AccountFormHtmlProvider\MobileHtmlRenderer;
use AwardWallet\MainBundle\Service\AccountFormHtmlProvider\ProviderLinks;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @deprecated
 * use \AwardWallet\MainBundle\Service\AccountFormHtmlProvider\DesktopHtmlRenderer,
 * \AwardWallet\MainBundle\Service\AccountFormHtmlProvider\MobileHtmlRenderer instead
 */
class EmailHelper
{
    private MobileHtmlRenderer $mobileHtmlRenderer;

    public function __construct(MobileHtmlRenderer $mobileHtmlRenderer)
    {
        $this->mobileHtmlRenderer = $mobileHtmlRenderer;
    }

    /**
     * @return Message[]
     */
    public function getMessages(array $accountFields, array $userFields): array
    {
        $symfonyContainer = getSymfonyContainer();
        $authChecker = $symfonyContainer->get('security.authorization_checker');
        $isBusiness = $authChecker->isGranted('SITE_BUSINESS_AREA');
        $editMode = !empty($accountFields['AccountID']);
        $isPerksplus = $accountFields['Code'] === 'perksplus';

        $account = null;

        if ($editMode) {
            $account = $symfonyContainer->get('doctrine')->getRepository(Account::class)->find($accountFields['AccountID']);
        }

        if (!$isBusiness && $editMode && !$authChecker->isGranted('EDIT', $account)) {
            return [];
        }

        $result = [];

        // Add provider-specific warning messages first
        $result = array_merge($result, [$this->getProviderWarningMessage($accountFields['Code'] ?? '')]);

        // Get provider-specific links
        $providerLinks = ProviderLinks::get($accountFields['Code'] ?? '');
        $profileLink = $providerLinks['profileLink'];
        $emailProfileLink = $providerLinks['emailProfileLink'];
        $screenshotLink = $providerLinks['screenshotLink'] ?? null;

        $router = $symfonyContainer->get('router');
        $urlGenerator = DynamicUtils::createArrayAccessImpl(function (string $route) use ($router): string {
            return $router->generate($route, [], UrlGeneratorInterface::ABSOLUTE_URL);
        });
        $isSiteMobileArea = $authChecker->isGranted('SITE_MOBILE_AREA');
        $isEmailFieldParse = !$isSiteMobileArea && $editMode && !$isPerksplus;

        if ($isBusiness && !empty($accountFields['UserID'])) {
            $userId = $accountFields['UserID'];
            $user = $symfonyContainer->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);

            if ($user) {
                $userFields['Login'] = $user->getLogin();
            }
        }

        if (!empty($screenshotLink)) {
            $screenshotLink = " (<a href='{$screenshotLink}' target='_blank'>as shown on this screenshot</a>)";
        }

        $autologinLinkValidator = $symfonyContainer->get(AutologinLinkValidator::class);
        $autologinLink = $router->generate('aw_account_redirect', ['ID' => $accountFields['AccountID'] ?? 0, 'TargetURL' => '', 'Signature' => $autologinLinkValidator->createSignature('')]);

        if ($editMode && !$isSiteMobileArea) {
            $loginLinkStart = "<a href='" . $autologinLink . "' target='_blank'>";
            $loginLinkEnd = "</a>";
        } else {
            $loginLinkStart = '';
            $loginLinkEnd = '';
        }

        if (
            !$isSiteMobileArea
            || $symfonyContainer->get('aw.api.versioning')->supports(MobileVersions::MAILBOX_SCANNER)
        ) {
            $ua = $symfonyContainer->get('request_stack')->getCurrentRequest()->headers->get('user-agent');
            $keys = (false !== strpos($ua, 'Macintosh') || false !== strpos($ua, 'Mac OS')) ? 'Cmd-A, Cmd-C, Cmd-V' : 'Ctrl-A, Ctrl-C, Ctrl-V';

            if (!$isPerksplus) {
                $msgAppend = $isEmailFieldParse ? '' : "<br><br>
                    If you don't want to wait for the statement to arrive you can test this functionality by simply copy-pasting ({$keys}) the entire {$loginLinkStart}account details page{$loginLinkEnd} from the {$accountFields['ProgramName']} website
                    into a new email message and send it to <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>.";
            } else {
                $msgAppend = '';
            }

            $result[] = new Message("The best and simplest way to automate the tracking of your {$accountFields['ShortName']} account is to <a href='{$urlGenerator['aw_usermailbox_view']}'>link your mailbox to AwardWallet</a>, the one where {$accountFields['ShortName']} sends its monthly statements. However, you need to do that after you add your {$accountFields['ShortName']} account via this page.
            <br><br>
            If you are uncomfortable linking your mailbox to AwardWallet, the second-best option to track your {$accountFields['ShortName']} account is to set up auto-forwarding of your {$accountFields['ShortName']} statements to us. 
            Your email address on AwardWallet is <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>. You will need to add this address as a forwarding address to your email. 
            Please note that we auto-approve forwarding requests from Google (Gmail), so there is no need to contact our support for that. 
            Also, <a href='https://awardwallet.com/blog/how-to-track-delta-southwest-united-accounts-awardwallet/' target='_blank'>our blog post</a> goes into detail (with screenshots) on how to set this up. 
            When setting up a forwarding rule inside Gmail, we recommend using the <a href='" . $router->generate('aw_gmail_forwarding') . "' target='_blank'>following guide</a>, which will also forward any travel reservations you book to your AwardWallet account. If you are not interested in AwardWallet tracking your travel plans, you can simply set up the following search query (with curly brackets):
            
            <br><br>
                <b>{from:delta.com from:united.com from:southwest.com from:aa.com}</b>
            <br><br>
            
            and set up forwarding of all emails from these domains to your AwardWallet address <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>.
            
			<br><br>
			" . (
                $isPerksplus
                    ? ''
                    : "
                    In either case, you need to make sure you opt-in to receive {$accountFields['ShortName']} statements by subscribing for html emails under
                    the <a href='{$emailProfileLink}' target='_blank'>{$accountFields['ProgramName']} Statement</a> heading.
                    <br><br>   
                "
            ) . "
			<span  id=\"update-manual\"></span>
			If you have multiple {$accountFields['ShortName']} accounts you can specify your email as your AwardWallet login name dot your {$accountFields['ShortName']} account login, i.e.:
			<span class=\"userEmail\"><nobr>{$userFields['Login']}." . (!empty($accountFields['Login']) ? htmlspecialchars($accountFields['Login']) : "[Your" . str_replace(" ",
                "", $accountFields['ProgramName']) . "Login]") . "@email.awardwallet.com</nobr></span>
			" . $msgAppend);
        } else {
            $result[] = new Message("You have a personal mailbox with AwardWallet: <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>,
			feel free to forward your {$accountFields['ShortName']} statements to this mailbox to have them automatically imported into your account.
			You can set up your email program to automatically forward those {$accountFields['ShortName']} statements and itineraries.
			<br><br>
			Alternatively, you can <a href='{$profileLink}' target='_blank'>update the email on your {$accountFields['ProgramName']} profile</a>
			with <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>{$screenshotLink}. Don't worry, any email we get for you from {$accountFields['ShortName']} will
			be forwarded to the email stored on your <a href='/user/profile' target='_blank'>AwardWallet account</a>.
			<br><br>
			In either case, you need to make sure you opt-in to receive {$accountFields['ShortName']} statements by subscribing for html emails under
			the <a href='{$emailProfileLink}' target='_blank'>{$accountFields['ProgramName']} Statement</a> heading.
			<br><br>
			If you have multiple {$accountFields['ShortName']} accounts you can specify your email as your AwardWallet login name dot your {$accountFields['ShortName']} account login, i.e.:
			<span class=\"userEmail\"><nobr>{$userFields['Login']}." . (!empty($accountFields['Login']) ? htmlspecialchars($accountFields['Login']) : "[Your" . str_replace(" ",
                "", $accountFields['ProgramName']) . "Login]") . "@email.awardwallet.com</nobr></span>
			<br><br>
			If you don't want to wait for the statement to arrive you can test this functionality by simply copy-pasting (Ctrl-A, Ctrl-C, Ctrl-V) the entire {$loginLinkStart}account details page{$loginLinkEnd} from the {$accountFields['ProgramName']} website
			into a new email message and send it to <span class=\"userEmail\">{$userFields['Login']}@email.AwardWallet.com</span>.
			");
        }

        if ($isEmailFieldParse && isset($keys)) {
            $translator = $symfonyContainer->get('translator');
            $result[] = new Message('
                If you don\'t want to wait for the statement to arrive you can test this functionality by simply copy-pasting (' . $keys . ') <a href="' . $autologinLink . '" id="autologin_link"  target="_blank">the entire account details page</a> from the website into this window:
                <div id="emailParse" class="email-parse">
                    <iframe id="emailBody" class="email-body" src="javascript:\'\'"></iframe>
                    <div class="email-files"></div>
                    <div id="emailWrap" class="email-wrap main-blk full-width" style="display: none">
                        <div class="main-form-event">
                            <div class="update">
                                <i class="icon-silver-arrow-down"></i><p>' . $translator->trans('award.account.form.checking.title') . '</p>
                                <div class="progress-bar">
                                    <div class="progress-bar-row">
                                        <p>1%</p><span style="width:1%"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="email-error">
                                <div class="error-message"><i class="icon-red-error-b"></i><p><span class="red">' . $translator->trans('award.account.form.failed.title') . '</span></p></div>
                                <div class="error-message-blk"><p></p></div>
                            </div>
                            <div class="email-success">
                                <div class="success">
                                    <div class="success-blk">
                                        <i id="email-account-icon-changed"></i>
                                        <h5>' . $translator->trans('award.account.form.success.text') . '</h5>
                                        <table>
                                            <tbody>
                                            <tr>
                                                <td>' . $translator->trans('award.account.form.changed.old-balance') . ':</td>
                                                <td class="email-account-lastbalance"></td>
                                            </tr>
                                            <tr>
                                                <td>' . $translator->trans('award.account.form.changed.changed') . ':</td>
                                                <td><span id="email-account-balance-changed"></span></td>
                                            </tr>
                                            <tr>
                                                <td><span class="bold">' . $translator->trans('award.account.form.success.balance') . ':</span></td>
                                                <td><span class="balance"></span></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="email-done">
                                <button class="btn-blue" type="button">' . $translator->trans('button.ok') . '</button>
                            </div>
                        </div>
                    </div>
                    <p class="email-submit">
                        <button class="btn-blue" type="button" disabled>Process</button>
                    </p>
                </div>
            ');
        }

        if ($editMode) {
            $checkDate = isset($accountFields["EmailParseDate"]) ? strtotime($accountFields["EmailParseDate"]) : false;

            if ($checkDate) {
                $diff = intval((time() - $checkDate) / SECONDS_PER_DAY);

                if ($diff > 30) {
                    $email = $userFields['Login'] . "@email.awardwallet.com";
                    $icon = 'warning';
                    $text = "
					Last time we received a valid statement for this {$accountFields['ProgramName']} account <span class='days'><nobr>{$diff} days ago.</nobr></span>
					It's been more than a month, probably your statements are not being automatically forwarded to {$email}";
                } else {
                    $icon = 'success';
                    $days = ($diff == 1 ? "1 day ago" : "$diff days ago");
                    $text = "Last time we received a valid statement for this {$accountFields['ProgramName']} account <span class='days'><nobr>{$days}.</nobr></span><br>Your updates seem to be working.";
                }
            } else {
                $icon = 'warning';
                $text = "At this point we have not received any valid {$accountFields['ProgramName']} statements for this account into your	personal mailbox.";
            }
            $result[] = new Message($text, "statementNotice", $icon);
        }

        return $result;
    }

    private function getProviderWarningMessage(string $providerCode): Message
    {
        switch ($providerCode) {
            case 'delta':
            case 'deltacorp':
                return $this->getDeltaWarningMessage($providerCode);

            case 'rapidrewards':
                return $this->getSouthwestWarningMessage();

            case 'mileageplus':
            case 'perksplus':
                return $this->getUnitedWarningMessage($providerCode);
        }
    }

    /**
     * Get Delta warning message.
     */
    private function getDeltaWarningMessage(string $providerCode): Message
    {
        return new Message(
            $this->mobileHtmlRenderer->getProviderWarning($providerCode),
            'alert',
            null,
            'Unfortunately Delta Airlines forced us to stop supporting their loyalty programs.'
        );
    }

    /**
     * Get Southwest warning message.
     */
    private function getSouthwestWarningMessage(): Message
    {
        return new Message(
            $this->mobileHtmlRenderer->getProviderWarning('rapidrewards'),
            'alert',
            null,
            'Unfortunately Southwest is not allowing us to pull data from their website anymore'
        );
    }

    private function getUnitedWarningMessage(string $providerCode): Message
    {
        return new Message(
            $this->mobileHtmlRenderer->getProviderWarning($providerCode),
            'alert',
            null,
            'Unfortunately United Airlines forced us to stop supporting their loyalty programs'
        );
    }
}
