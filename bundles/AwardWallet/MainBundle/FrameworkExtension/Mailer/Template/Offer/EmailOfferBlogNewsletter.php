<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Offer;

class EmailOfferBlogNewsletter extends EmailOffer
{
    public $blogpostsNewsletter;

    public static function getDescription(): string
    {
        return 'Static offer blog newsletter';
    }

    public function sortPosts(string $orderList, array $blogposts): array
    {
        $orderList = explode(',', $orderList);
        $orderList = array_map('trim', $orderList);
        $orderList = array_map('intval', $orderList);

        $result = [];

        foreach ($orderList as $postId) {
            if (!array_key_exists($postId, $blogposts)) {
                continue;
            }

            $result[$postId] = $blogposts[$postId];
        }

        return $result;
    }

    public function generateBlogPostsNewsletter(?array $blogposts, array $params): string
    {
        if (empty($blogposts)) {
            return '';
        }

        $html = '<div style="padding: 0 20px;"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody>';

        foreach ($blogposts as $blogpost) {
            $title = $blogpost['title'];
            $excerpt = $blogpost['excerpt'] ?? $blogpost['description'];
            $image = $blogpost['imageURL'] ?? $blogpost['thumbnail'];

            $link = $blogpost['postURL'];
            $args = [
                'utm_source' => 'aw',
                'utm_medium' => $params['mid'],
                'utm_campaign' => $params['cid'],
                'awid' => 'aw',
                'mid' => $params['mid'],
                'cid' => $params['cid'],
                'rkbtyn' => '{{ RefCode }}',
            ];
            $query = parse_url($link, PHP_URL_QUERY);

            if (!empty($query)) {
                parse_str($query, $queryVars);
                $link = str_replace($query, urldecode(http_build_query(array_merge($queryVars, $args))), $link);
            } else {
                $link .= '?' . urldecode(http_build_query($args));
            }

            $html .= <<< HTML
            <tr>
                <td style="line-height: 24px; font-size: 15px; border-bottom: solid 1px #3d424d; padding: 20px 0 10px">
                    <a class="title-link" href="{$link}" style="font-size: 24px; line-height: 30px; font-weight: 900; font-family: 'Muli', sans-serif; text-decoration: none;color: #3d424d;" target="_blank">{$title}</a>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <td style="font-size: 0; text-align: center; padding-top: 20px; padding-bottom: 10px">
                                <div style="display: inline-block; vertical-align: top;"><!-- Container: Column One -->
                                    <table align="left" border="0" cellpadding="0" cellspacing="0" width="220">
                                    <tbody>
                                        <tr>
                                            <td style="padding-right: 20px; padding-bottom: 10px">
                                                <img alt="" border="0" src="{$image}" style="vertical-align: top; max-height: auto !important; min-height: auto !important; height: auto !important; max-width: 100% !important; width: 100% !important; border-width: 0 !important;">
                                            </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </div>

                                <div style="display: inline-block; vertical-align: top;"><!-- Container: Column Two -->
                                    <table align="left" border="0" cellpadding="0" cellspacing="0" style="max-width: 515px; width: auto">
                                    <tbody>
                                        <tr>
                                            <td style="text-align: left;  color: #3d424d; font-size: 15px; line-height: 26px;font-family: Arial, sans-serif;">
                                                {$excerpt}
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td align="right" style="padding-top: 15px" valign="middle"><a href="{$link}" style="color: #4684c4; line-height: 20px; font-weight: 900; text-decoration: none; font-size: 15px; font-family: 'Muli', sans-serif;" target="_blank">Read More
                                                            <img alt="" border="0" height="19" src="https://awardwallet.com/images/uploaded/emails/ads/founderscard/more-icon.png" style="margin-left: 5px; vertical-align: middle;" width="19"/> </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </td>
            </tr>
HTML;
        }
        $html .= '</tbody></table></div>';

        return $html;
    }
}
