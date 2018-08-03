<?php

namespace Shopsys\FrameworkBundle\Form\Admin\Product;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\CategoriesType;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\FormRenderingConfigurationExtension;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\Locale\LocalizedType;
use Shopsys\FrameworkBundle\Form\UrlListType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ProductFormType extends AbstractType
{
    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';
    const VALIDATION_GROUP_AUTO_PRICE_CALCULATION = 'autoPriceCalculation';
    const VALIDATION_GROUP_USING_STOCK = 'usingStock';
    const VALIDATION_GROUP_USING_STOCK_AND_ALTERNATE_AVAILABILITY = 'usingStockAndAlternateAvailability';
    const VALIDATION_GROUP_NOT_USING_STOCK = 'notUsingStock';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade
     */
    private $availabilityFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade
     */
    private $unitFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade
     */
    private $seoSettingFacade;

    public function __construct(
        VatFacade $vatFacade,
        AvailabilityFacade $availabilityFacade,
        BrandFacade $brandFacade,
        FlagFacade $flagFacade,
        UnitFacade $unitFacade,
        Domain $domain,
        SeoSettingFacade $seoSettingFacade
    ) {
        $this->vatFacade = $vatFacade;
        $this->availabilityFacade = $availabilityFacade;
        $this->brandFacade = $brandFacade;
        $this->flagFacade = $flagFacade;
        $this->unitFacade = $unitFacade;
        $this->domain = $domain;
        $this->seoSettingFacade = $seoSettingFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $vats = $this->vatFacade->getAllIncludingMarkedForDeletion();

        $product = $options['product'];
        /* @var $product \Shopsys\FrameworkBundle\Model\Product\Product|null */

        if ($product !== null && $product->isVariant()) {
            $builder->add('variantAlias', LocalizedType::class, [
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Constraints\Length(['max' => 255, 'maxMessage' => 'Variant alias cannot be longer then {{ limit }} characters']),
                    ],
                ],
            ]);
        }

        $seoTitlesOptionsByDomainId = [];
        $seoMetaDescriptionsOptionsByDomainId = [];
        $seoH1OptionsByDomainId = [];
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            $locale = $domainConfig->getLocale();

            $seoTitlesOptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->getTitlePlaceholder($locale, $product),
                    'class' => 'js-dynamic-placeholder',
                    'data-placeholder-source-input-id' => 'product_edit_form_productData_name_' . $locale,
                ],
            ];
            $seoMetaDescriptionsOptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->seoSettingFacade->getDescriptionMainPage($domainId),
                ],
            ];
            $seoH1OptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->getTitlePlaceholder($locale, $product),
                    'class' => 'js-dynamic-placeholder',
                    'data-placeholder-source-input-id' => 'product_edit_form_productData_name_' . $locale,
                ],
            ];
        }
        $disabledItemInMainVariantAttr = [];
        if($product->isMainVariant()) {
            $disabledItemInMainVariantAttr = [
                'disabledField' => true,
                'disabledField__title' => t('This item can be set in product detail of a specific variant'),
                'disabledField__class' => 'form-line__disabled',
            ];
        }

        $categoriesOptionsByDomainId = [];
        foreach ($this->domain->getAllIds() as $domainId) {
            $categoriesOptionsByDomainId[$domainId] = [
                'domain_id' => $domainId,
            ];
        }

        $builderBasicInformationGroup = $builder->create('basicInformation', GroupType::class, [
            'label' => t('Basic information'),
        ]);

        $builder
            ->add('name', LocalizedType::class, [
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Constraints\Length(['max' => 255, 'maxMessage' => 'Product name cannot be longer than {{ limit }} characters']),
                    ],
                ],
                'label' => t('Name'),
            ]);

            # basicInfoGroup
        $builderBasicInformationGroup->add('catnum', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'Catalogue number cannot be longer then {{ limit }} characters']),
                ],
                'disabled' => $product->isMainVariant(),
                'attr' => $disabledItemInMainVariantAttr,
                'label' => t('Catalogue number'),
            ])
            ->add('partno', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'Part number cannot be longer than {{ limit }} characters']),
                ],
                'disabled' => $product->isMainVariant(),
                'attr' => $disabledItemInMainVariantAttr,
                'label' => t('PartNo (serial number)'),
            ])
            ->add('ean', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'EAN cannot be longer then {{ limit }} characters']),
                ],
                'disabled' => $product->isMainVariant(),
                'attr' => $disabledItemInMainVariantAttr,
                'label' => t('EAN'),
            ]);

            if ($product !== null) {
                $builderBasicInformationGroup->add('id', DisplayOnlyType::class, [
                    'label' => t('ID'),
                    'data' => $product->getId(),
                ]);
            }

            $builderBasicInformationGroup
            ->add('flags', ChoiceType::class, [
                'required' => false,
                'choices' => $this->flagFacade->getAll(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
                'label' => t('Flags'),
            ])
            ->add('brand', ChoiceType::class, [
                'required' => false,
                'choices' => $this->brandFacade->getAll(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('-- Choose brand --'),
                'label' => t('Brand'),
            ]);

            # DISLPAY AND AVAILABILITY
            $builder
                ->add($builderBasicInformationGroup);


            $builderDisplayAvailabilityGroup = $builder->create('displayAvailability', GroupType::class, [
                'label' => t('Display and availability'),
            ]);

            $builderDisplayAvailabilityGroup
                ->add('hidden', YesNoType::class, [
                    'required' => false,
                    'label' => t('Hide product')
                ])
                ->add('sellingFrom', DatePickerType::class, [
                    'required' => false,
                    'constraints' => [
                        new Constraints\Date(['message' => 'Enter date in DD.MM.YYYY format']),
                    ],
                    'invalid_message' => 'Enter date in DD.MM.YYYY format',
                    'label' => t('Selling start date'),
                ])
                ->add('sellingTo', DatePickerType::class, [
                    'required' => false,
                    'constraints' => [
                        new Constraints\Date(['message' => 'Enter date in DD.MM.YYYY format']),
                    ],
                    'invalid_message' => 'Enter date in DD.MM.YYYY format',
                    'label' => t('Selling end date'),
                ])
                ->add('sellingDenied', YesNoType::class, [
                    'required' => false,
                    'label' => t('Exclude from sale'),
                ])
                ->add('categoriesByDomainId', MultidomainType::class, [
                    'required' => false,
                    'entry_type' => CategoriesType::class,
                    'options_by_domain_id' => $categoriesOptionsByDomainId,
                    'label' => t('Assign to category'),
                    'display_format' => FormRenderingConfigurationExtension::DISPLAY_FORMAT_MULTIDOMAIN_ROWS_NO_PADDING,
                ])
                ->add('unit', ChoiceType::class, [
                    'required' => true,
                    'choices' => $this->unitFacade->getAll(),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please choose unit',
                        ]),
                    ],
                    'label' => t('Unit'),
                ])
                ->add('usingStock', YesNoType::class, [
                    'required' => false,
                    'disabled' => $product->isMainVariant(),
                    'attr' => $disabledItemInMainVariantAttr,
                    'label' => t('Use stocks'),
                ])
                ->add('stockQuantity', IntegerType::class, [
                    'required' => true,
                    'invalid_message' => 'Please enter a number',
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please enter stock quantity',
                            'groups' => self::VALIDATION_GROUP_USING_STOCK,
                        ]),
                    ],
                    'disabled' => $product->isMainVariant(),
                    'attr' => $disabledItemInMainVariantAttr,
                    'label' => t('Available in stock'),
                ])
                ->add('outOfStockAction', ChoiceType::class, [
                    'required' => true,
                    'expanded' => false,
                    'choices' => [
                        t('Set alternative availability') => Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY,
                        t('Hide product') => Product::OUT_OF_STOCK_ACTION_HIDE,
                        t('Exclude from sale') => Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE,
                    ],
                    'placeholder' => t('-- Choose action --'),
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please choose action',
                            'groups' => self::VALIDATION_GROUP_USING_STOCK,
                        ]),
                    ],
                    'disabled' => $product->isMainVariant(),
                    'attr' => $disabledItemInMainVariantAttr,
                    'label' => t('Action after sellout'),
                ])
                ->add('outOfStockAvailability', ChoiceType::class, [
                    'required' => true,
                    'choices' => $this->availabilityFacade->getAll(),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'placeholder' => t('-- Choose availability --'),
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please choose availability',
                            'groups' => self::VALIDATION_GROUP_USING_STOCK_AND_ALTERNATE_AVAILABILITY,
                        ]),
                    ],
                    'disabled' => $product->isMainVariant(),
                    'attr' => $disabledItemInMainVariantAttr,
                    'label' => t('Availability after sellout'),
                ])
                ->add('availability', ChoiceType::class, [
                    'required' => true,
                    'choices' => $this->availabilityFacade->getAll(),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'placeholder' => t('-- Choose availability --'),
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please choose availability',
                            'groups' => self::VALIDATION_GROUP_NOT_USING_STOCK,
                        ]),
                    ],
                    'disabled' => $product->isMainVariant(),
                    'attr' => $disabledItemInMainVariantAttr,
                    'label' => t('Availability'),
                ])
                ->add('orderingPriority', IntegerType::class, [
                    'required' => true,
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter sorting priority']),
                    ],
                    'label' => t('Sorting priority'),
                ]);

            $builder->add($builderDisplayAvailabilityGroup);

            $builderPricesGroup = $builder->create('prices', GroupType::class, [
                'label' => t('Prices'),
            ]);

            $builder
                ->add('priceCalculationType', ChoiceType::class, [
                    'required' => true,
                    'expanded' => true,
                    'choices' => [
                        t('Automatically') => Product::PRICE_CALCULATION_TYPE_AUTO,
                        t('Manually') => Product::PRICE_CALCULATION_TYPE_MANUAL,
                    ],
                    'label' => t('Sale prices calculation'),
                ])
                ->add('vat', ChoiceType::class, [
                    'required' => true,
                    'choices' => $vats,
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter VAT rate']),
                    ],
                    'label' => t('VAT'),
                ])
                ->add('price', MoneyType::class, [
                    'currency' => false,
                    'scale' => 6,
                    'required' => true,
                    'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please enter price',
                            'groups' => self::VALIDATION_GROUP_AUTO_PRICE_CALCULATION,
                        ]),
                        new Constraints\GreaterThanOrEqual([
                            'value' => 0,
                            'message' => 'Price must be greater or equal to {{ compared_value }}',
                            'groups' => self::VALIDATION_GROUP_AUTO_PRICE_CALCULATION,
                        ]),
                    ],
                ]);

            $builder->add($builderPricesGroup);

            $builderDescriptionGroup = $builder->create('descriptionGroup', GroupType::class, [
                'label' => t('Description'),
            ]);

            # Description
            $builderDescriptionGroup
                ->add('descriptions', MultidomainType::class, [
                    'entry_type' => CKEditorType::class,
                    'required' => false,
                    'display_format' => FormRenderingConfigurationExtension::DISPLAY_FORMAT_MULTIDOMAIN_ROWS_NO_PADDING,
                ]);

            $builder->add($builderDescriptionGroup);

            $builderShortDescriptionGroup = $builder->create('shortDescription', GroupType::class, [
                'label' => t('Short description'),
            ]);

            # Short Description
            $builderShortDescriptionGroup
                ->add('shortDescriptions', MultidomainType::class, [
                    'entry_type' => TextareaType::class,
                    'required' => false,
                    'display_format' => FormRenderingConfigurationExtension::DISPLAY_FORMAT_MULTIDOMAIN_ROWS_NO_PADDING,
                ]);
            $builder->add($builderShortDescriptionGroup);

            $builderSeoGroup = $builder->create('seo', GroupType::class, [
                'label' => t('Seo'),
            ]);
            $builderSeoGroup
                ->add('seoTitles', MultidomainType::class, [
                    'entry_type' => TextType::class,
                    'required' => false,
                    'options_by_domain_id' => $seoTitlesOptionsByDomainId,
                    'macro' => [
                        'name' => 'seoFormRowMacros.multidomainRow',
                        'recommended_length' => 60,
                    ],
                    'label' => t('Page title'),
                ])
                ->add('seoMetaDescriptions', MultidomainType::class, [
                    'entry_type' => TextareaType::class,
                    'required' => false,
                    'options_by_domain_id' => $seoMetaDescriptionsOptionsByDomainId,
                    'macro' => [
                        'name' => 'seoFormRowMacros.multidomainRow',
                        'recommended_length' => 155,
                    ],
                    'label' => t('Meta description'),
                ])
                ->add('seoH1s', MultidomainType::class, [
                    'entry_type' => TextType::class,
                    'required' => false,
                    'options_by_domain_id' => $seoH1OptionsByDomainId,
                    'label' => t('Heading (H1)'),
                ]);
    
            if ($product) {
                $builderSeoGroup->add('urls', UrlListType::class, [
                    'route_name' => 'front_product_detail',
                    'entity_id' => $product->getId(),
                    'label' => t('URL settings'),
                ]);
            }

            $builder->add($builderSeoGroup);

        if ($product !== null) {
            $this->disableIrrelevantFields($builder, $product);
        }
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('product')
            ->setAllowedTypes('product', [Product::class, 'null'])
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];
                    $productData = $form->getData();
                    /* @var $productData \Shopsys\FrameworkBundle\Model\Product\ProductData */

                    if ($productData->usingStock) {
                        $validationGroups[] = self::VALIDATION_GROUP_USING_STOCK;
                        if ($productData->outOfStockAction === Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY) {
                            $validationGroups[] = self::VALIDATION_GROUP_USING_STOCK_AND_ALTERNATE_AVAILABILITY;
                        }
                    } else {
                        $validationGroups[] = self::VALIDATION_GROUP_NOT_USING_STOCK;
                    }

                    if ($productData->priceCalculationType === Product::PRICE_CALCULATION_TYPE_AUTO) {
                        $validationGroups[] = self::VALIDATION_GROUP_AUTO_PRICE_CALCULATION;
                    }

                    return $validationGroups;
                },
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     */
    private function disableIrrelevantFields(FormBuilderInterface $builder, Product $product)
    {
        $irrelevantFields = [];
        if ($product->isMainVariant()) {
            $irrelevantFields = [
                'basicInformation' => [
                    'catnum',
                    'partno',
                    'ean',
                ],
                'displayAvailability' => [
                    'usingStock',
                    'stockQuantity',
                    'outOfStockAction',
                    'outOfStockAvailability',
                    'availability',
                ],

                'price',
                'vat',
                'priceCalculationType',
            ];
        }
        if ($product->isVariant()) {
            $irrelevantFields = [
                'displayAvailability' => [
                    'categoriesByDomainId',
                ],
                'descriptionGroup' => [
                    'descriptions',
                ],
            ];
        }
        foreach ($irrelevantFields as $irrelevantGroupKey => $irrelevantGroup) {
            if(is_array($irrelevantGroup)){
                $element = $builder->get($irrelevantGroupKey);
                foreach ($irrelevantGroup as $irrelevantField)
                {
                    $element->get($irrelevantField)->setDisabled(true);
                }
            }
            else{
                $builder->get($irrelevantGroup)->setDisabled(true);
            }
        }
    }

    /**
     * @param string $locale
     * @param \Shopsys\FrameworkBundle\Model\Product\Product|null $product
     * @return string
     */
    private function getTitlePlaceholder($locale, Product $product = null)
    {
        return $product !== null ? $product->getName($locale) : '';
    }

    private function isMainVariantAndEditMode($product, $options){
        return $options['scenario'] === self::SCENARIO_EDIT && $product->isMainVariant();
    }
}
