<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UserQuestion")
 * @ORM\Entity
 */
class UserQuestion
{
    public const ORDER_FIRST = 1;
    public const ORDER_SECOND = 2;
    public const ORDER_THIRD = 3;

    public const MAX_QUESTIONS_ALLOWED = 3;

    public const CHILDHOOD_NICKNAME = 'user.questions.childhood-nickname';
    public const MEETING_SPOUSE_SIGNIFICANT_OTHER = 'user.questions.meeting-spouse-significant-other';
    public const FAVORITE_CHILDHOOD_FRIEND = 'user.questions.favorite-childhood-friend';
    public const STREET_THIRD_GRADE = 'user.questions.street-third-grade';
    public const OLDEST_SIBLING_BIRTHDAY = 'user.questions.oldest-sibling-birthday';
    public const MIDDLE_NAME_YOUNGEST_CHILD = 'user.questions.middle-name-youngest-child';
    public const OLDEST_SIBLING_MIDDLE_NAME = 'user.questions.oldest-sibling-middle-name';
    public const SCHOOL_SIXTH_GRADE = 'user.questions.school-sixth-grade';
    public const CHILDHOOD_PHONE_NUMBER = 'user.questions.childhood-phone-number';
    public const OLDEST_COUSIN_NAME = 'user.questions.oldest-cousin-name';
    public const NAME_STUFFED_ANIMAL = 'user.questions.name-stuffed-animal';
    public const CITY_MOTHER_FATHER_MEETING = 'user.questions.city-mother-father-meeting';
    public const PLACE_FIRST_KISS = 'user.questions.place-first-kiss';
    public const NAME_BOY_GIRL_FIRST_KISS = 'user.questions.name-boy-girl-first-kiss';
    public const NAME_THIRD_GRADE_TEACHER = 'user.questions.name-third-grade-teacher';
    public const CITY_NEAREST_SIBLING = 'user.questions.city-nearest-sibling';
    public const YOUNGEST_BROTHER_BIRTHDAY = 'user.questions.youngest-brother-birthday';
    public const GRANDMOTHER_MAIDEN_NAME = 'user.questions.grandmother-maiden-name';
    public const CITY_FIRST_JOB = 'user.questions.city-first-job';
    public const PLACE_WEDDING_RECEPTION = 'user.questions.place-wedding-reception';
    public const COLLEGE_DIDNT_ATTEND = 'user.questions.college-didnt-attend';
    public const FIRST_HEARD_9_11 = 'user.questions.first-heard-9-11';

    /**
     * @var Usr
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    private $user;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="SortIndex", type="smallint", nullable=false)
     */
    private $order;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="Question", type="string", length=64, nullable=false)
     */
    private $question;

    /**
     * @var string
     * @ORM\Column(name="Answer", type="text", nullable=false)
     */
    private $answer;

    public function getUser(): Usr
    {
        return $this->user;
    }

    public function setUser(Usr $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public static function getOrdersArray(): array
    {
        return [
            self::ORDER_FIRST,
            self::ORDER_SECOND,
            self::ORDER_THIRD,
        ];
    }

    public static function getQuestionsArray(): array
    {
        return [
            self::CHILDHOOD_NICKNAME => 'user.questions.childhood-nickname',
            self::MEETING_SPOUSE_SIGNIFICANT_OTHER => 'user.questions.meeting-spouse-significant-other',
            self::FAVORITE_CHILDHOOD_FRIEND => 'user.questions.favorite-childhood-friend',
            self::STREET_THIRD_GRADE => 'user.questions.street-third-grade',
            self::OLDEST_SIBLING_BIRTHDAY => 'user.questions.oldest-sibling-birthday',
            self::MIDDLE_NAME_YOUNGEST_CHILD => 'user.questions.middle-name-youngest-child',
            self::OLDEST_SIBLING_MIDDLE_NAME => 'user.questions.oldest-sibling-middle-name',
            self::SCHOOL_SIXTH_GRADE => 'user.questions.school-sixth-grade',
            self::CHILDHOOD_PHONE_NUMBER => 'user.questions.childhood-phone-number',
            self::OLDEST_COUSIN_NAME => 'user.questions.oldest-cousin-name',
            self::NAME_STUFFED_ANIMAL => 'user.questions.name-stuffed-animal',
            self::CITY_MOTHER_FATHER_MEETING => 'user.questions.city-mother-father-meeting',
            self::PLACE_FIRST_KISS => 'user.questions.place-first-kiss',
            self::NAME_BOY_GIRL_FIRST_KISS => 'user.questions.name-boy-girl-first-kiss',
            self::NAME_THIRD_GRADE_TEACHER => 'user.questions.name-third-grade-teacher',
            self::CITY_NEAREST_SIBLING => 'user.questions.city-nearest-sibling',
            self::YOUNGEST_BROTHER_BIRTHDAY => 'user.questions.youngest-brother-birthday',
            self::GRANDMOTHER_MAIDEN_NAME => 'user.questions.grandmother-maiden-name',
            self::CITY_FIRST_JOB => 'user.questions.city-first-job',
            self::PLACE_WEDDING_RECEPTION => 'user.questions.place-wedding-reception',
            self::COLLEGE_DIDNT_ATTEND => 'user.questions.college-didnt-attend',
            self::FIRST_HEARD_9_11 => 'user.questions.first-heard-9-11',
        ];
    }
}
