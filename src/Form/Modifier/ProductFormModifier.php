<?php

namespace Respawnsive\Pga\Form\Modifier;


use PrestaShopBundle\Form\Admin\Type\Material\MaterialChoiceTableType;
use PrestaShopBundle\Form\FormBuilderModifier;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProductFormModifier
{
    /**
     * @param FormBuilderModifier $formBuilderModifier
     */
    public function __construct(
        private FormBuilderModifier $formBuilderModifier,
        private array $customerGroupChoices
    ) {
    }


    /**
     * @param int|null $productId
     * @param FormBuilderInterface $productFormBuilder
     */
    public function modify(
        int $productId,
        FormBuilderInterface $productFormBuilder,
        array $group_associations = []
    ): void {
        $builder = $productFormBuilder->get('description');
        $this->formBuilderModifier->addAfter(
            $builder,
            'description',
            'group_association',
            MaterialChoiceTableType::class,
            [
                'choices' => $this->customerGroupChoices,
                'data' => $group_associations,
                'label' => 'Accès des groupes',
                'row_attr' => [
                    'class' => 'respawnsive_pga',
                ],
                'attr' => [
                    'placeholder' => 'Accès des groupes',
                ],
                'form_theme' => '@PrestaShop/Admin/TwigTemplateForm/prestashop_ui_kit_base.html.twig',
            ]
        );
    }
}
