<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Form\Helper\FormOrderingHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;

use function PHPUnit\Framework\assertEquals;

/**
 * @group frontend-unit
 */
class FormOrderingHelperTest extends BaseContainerTest
{
    public function testAddBefore()
    {
        $builder = $this->getDefaultFormBuilder();
        $builder->add('second');
        (new FormOrderingHelper($builder))
            ->moveBefore('second', $builder->create('first'));
        $this->assertOrder(['first', 'second'], $builder);
    }

    public function testAddAfter()
    {
        $builder = $this->getDefaultFormBuilder();
        $builder
            ->add('first')
            ->add('third');

        (new FormOrderingHelper($builder))
            ->moveAfter('first', $builder->create('second'));
        $this->assertOrder(['first', 'second', 'third'], $builder);
    }

    public function testMoveBefore()
    {
        $builder = $this->getDefaultFormBuilder();
        $builder
            ->add('second')
            ->add('first');

        (new FormOrderingHelper($builder))->moveBefore('second', 'first');
        $this->assertOrder(['first', 'second'], $builder);
    }

    public function testMoveAfter()
    {
        $builder = $this->getDefaultFormBuilder();
        $builder
            ->add('first')
            ->add('third')
            ->add('second');

        (new FormOrderingHelper($builder))->moveAfter('second', 'third');
        $this->assertOrder(['first', 'second', 'third'], $builder);
    }

    protected function assertOrder(array $expected, FormBuilderInterface $formBuilder)
    {
        assertEquals($expected, array_keys($formBuilder->all()));
    }

    protected function getDefaultFormBuilder()
    {
        return new FormBuilder(
            'default',
            null,
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $this->container->get('form.factory')
        );
    }
}
