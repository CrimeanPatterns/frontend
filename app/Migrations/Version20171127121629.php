<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171127121629 extends AbstractMigration
{
    public const COLORS_CSV = "ProviderCode,BackgroundColor,FontColor
aarp,#ec3729,#ffffff
ace,#e31937,#ffffff
acmoore,#e12542,#ffffff
airmiles,#2f6ecd,#ffffff
americaneagle,#091e40,#ffffff
ap,#f7f7f7,#000000
auchan,#dd0b1e,#ffffff
austinreed,#1a1a1a,#ffffff
autozone,#1a1a1a,#ffffff
aveda,#392720,#ffffff
bashneft,#676767,#ffffff
bestbuy,#003b64,#ffffff
bevmo,#de2518,#ffffff
biglots,#e67504,#ffffff
boots,#00498f,#ffffff
buildabear,#0056a1,#ffffff
calvinklein,#1a1a1a,#ffffff
caprabo,#6fb3e2,#ffffff
carrefour,#04629e,#ffffff
changi,#9f83bb,#ffffff
columbia,#1586c6,#ffffff
coop,#e20000,#ffffff
cooperative,#00b0e6,#ffffff
cvs,#c90813,#ffffff
debenhams,#1a1a1a,#ffffff
dicks,#006554,#ffffff
dischem,#249345,#ffffff
dotz,#172860,#ffffff
dsw,#1a1a1a,#ffffff
eddiebauer,#414042,#ffffff
eldorado,#bd1535,#ffffff
eni,#fdd200,#222222
express,#231f20,#ffffff
famousfootwear,#231f20,#ffffff
finishline,#252525,#ffffff
flybuys,#007cc2,#ffffff
foodlion,#ffd844,#000000
foyles,#f7f7f7,#000000
fredmeyer,#ed1c24,#ffffff
freebees,#f7f7f7,#000000
gamestop,#333334,#ffffff
gap,#1c4e7b,#ffffff
gnc,#e31837,#ffffff
guess,#1a1a1a,#ffffff
gymboree,#f7971f,#ffffff
hallmark,#613790,#ffffff
harristeeter,#169c6e,#ffffff
harrods,#1a1a1a,#ffffff
heb,#dc2a27,#ffffff
heinemann,#001032,#ffffff
hottopic,#1a1a1a,#ffffff
hyvee,#ee3324,#ffffff
izod,#a93439,#ffffff
karusel,#f7f7f7,#1a1a1a
kingsoopers,#e31f26,#ffffff
kroger,#0067b1,#ffffff
letoile,#004489,#ffffff
lids,#194478,#ffffff
livrariacultura,#32a1a9,#ffffff
londondrugs,#005daa,#ffffff
longos,#ee3122,#ffffff
loves,#ffea00,#535457
lowes,#005e9f,#ffffff
lukoil,#da2b36,#ffffff
marsh,#f6f7f7,#535457
mediamarkt,#df0000,#ffffff
menswearhouse,#1a1a1a,#ffffff
metro,#e12526,#ffffff
michaels,#e12526,#ffffff
migros,#ff6600,#ffffff
milliplein,#12386e,#ffffff
moneyback,#00a1a1,#ffffff
monsoon,#1a1a1a,#ffffff
morrisons,#00563f,#ffffff
multiplus,#eaeaea,#000000
mvideo,#ed1c24,#ffffff
nectar,#471e5e,#ffffff
nordstrom,#f3f3f5,#000000
officedepot,#ce0000,#ffffff
officemax,#f7f7f7,#000000
panvel,#003986,#ffffff
pepboys,#e31837,#ffffff
perekrestok,#005826,#ffffff
petco,#f7f7f7,#000000
petrocanada,#ee1122,#ffffff
picknpay,#3676bd,#ffffff
plenti,#273691,#ffffff
purehmv,#ed027e,#ffffff
raleys,#ed3024,#ffffff
ralphs,#e4412b,#ffffff
redcube,#1a1a1a,#ffffff
rei,#1a1a1a,#ffffff
riteaid,#0d4a79,#ffffff
rivegauche,#1a1a1a,#ffffff
safeway,#e41e26,#ffffff
samsclub,#0069aa,#ffffff
saraiva,#ffca00,#000000
sephora,#1a1a1a,#ffffff
shoppersdrugmart,#e21a23,#ffffff
shoprite,#ed1b2e,#ffffff
silpo,#f47b20,#ffffff
smiles,#f47920,#ffffff
sobeys,#44a13f,#ffffff
sony,#1a1a1a,#ffffff
speedway,#ed174c,#ffffff
sportmaster,#005aab,#ffffff
stadium,#1a1a1a,#ffffff
staples,#cc0000,#ffffff
sunoco,#e9e9e9,#000000
superdrug,#ee0088,#ffffff
target,#cc0000,#ffffff
tesco,#0a55a0,#ffffff
texaco,#1a1a1a,#ffffff
tops,#d31145,#ffffff
totalerg,#ff0019,#ffffff
toysrus,#f7f7f7,#000000
trumf,#0076df,#ffffff
ulta,#f47d39,#ffffff
vons,#e41e26,#ffffff
waitrose,#6fa432,#ffffff
walgreens,#e21937,#ffffff
waterstones,#9e866b,#ffffff
westmarine,#0061aa,#ffffff
woolworths,#f57921,#ffffff
wegmans,#232323,#ffffff";

    public function up(Schema $schema): void
    {
        $this->connection->executeQuery("ALTER TABLE Provider 
              ADD BackgroundColor VARCHAR(8) NULL COMMENT 'Цвет фона провайдера' AFTER isRetail,
              ADD FontColor VARCHAR(8) NULL COMMENT 'Цвет шрифта провайдера' AFTER BackgroundColor");

        $stm = $this->connection->prepare("UPDATE Provider SET BackgroundColor = ?, FontColor = ? WHERE Code = ?");
        $this->write("store colors to database");
        $this->connection->transactional(function () use ($stm) {
            $arr = explode("\n", self::COLORS_CSV);
            unset($arr[0]);
            $csv = array_map('str_getcsv', $arr);

            foreach ($csv as $row) {
                $row = array_map(function ($v) {
                    return ltrim($v, "#");
                }, $row);
                $this->write(sprintf("%s: background: %s, font: %s", $row[0], $row[1], $row[2]));
                $stm->execute([$row[1], $row[2], $row[0]]);
            }
        });
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE Provider 
              DROP COLUMN BackgroundColor,
              DROP COLUMN FontColor
        ");
    }
}
