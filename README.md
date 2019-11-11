[![Software License][ico-license]](LICENSE.md)
[![Latest Stable Version](https://poser.pugx.org/edfa3ly-backend/dto-hydrator/v/stable)](https://packagist.org/packages/edfa3ly-backend/dto-hydrator)
[![Total Downloads](https://poser.pugx.org/edfa3ly-backend/dto-hydrator/downloads)](https://packagist.org/packages/edfa3ly-backend/dto-hydrator)

# Description

Dynamic Hydration for DTO's used by Transformers in [API Platform](https://api-platform.com) .

## Install

`composer require edfa3ly-backend/dto-hydrator`

## Usage

While using API Platform , using custom DTO's (defining custom input/output in the `@ApiResource`) requires you to deal with custom
 transformers specially when your DTO represents an Entity .
 
1. When using an input transformer **i.e : after the request body is denormalized into a DTO** , 
you should dehydrate the DTO **i.e** transform it to an entity in order to be persisted .

2. When using an output transformer **i.e : after the entity is retrieved and waiting to be normalized** , 
you should hydrate the DTO **i.e** transform the entity into a DTO in order to be displayed .

3. The embedded objects inside the Dto must be an `@ApiResource` in order to generate the swagger documentation

4. If the embedded objects defines `id` within the denormalized
request body it will be referenced and the data will be updated , either it will be persisted as a new Entity.
 
## Example

```
use Symfony\Component\Serializer\Annotation\Groups;

class OrderDto {

    /**
     * @var integer $id
     * @Groups({"order:read","order:put"})
     */
    protected $id;

    /**
     * @var Customer $customer
     * @Groups({"order:read","order:put"})
     */
    protected $customer;

    /**
     * @var Product[] $products
     * @Groups({"order:read","order:put"})
     */
    protected $products;

    /**
     * @return int
    */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @param int $id
    */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return Customer
    */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }
    
    /**
     * @param Customer $customer
    */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Product[]
    */
    public function getProducts(): array
    {
        return $this->products;
    }
    
    /**
     * @param Product[] $products
    */
    public function setProducts(array $products)
    {
        $this->products = $products;
    }
}

```

```
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table
 * @ORM\Entity
 * @ApiResource(
 *     itemOperations={
 *        "get":{
 *           "method":"get",
 *           "input":false,
 *           "output":OrderDto::class,
 *           "status":200,
 *           "normalization_context":{"groups":{"order:read"}}
 *        },
 *        "put":{
 *           "method":"put",
 *           "input":OrderDto::class,
 *           "output":OrderDto::class,
 *           "status":200,
 *           "denormalization_context":{"groups":{"order:put"}},
 *           "normalization_context":{"groups":{"order:read"}}
 *        }
 *     },
 *     collectionOperations={}
 * )
*/
class Order {
    /**
    * @var integer
    *
    * @ORM\Column(name="id", type="integer", nullable=false)
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="IDENTITY")
    * @ApiProperty(identifier=true)
    */
   protected $id; 

    /**
     * @var Customer $customer
     * @ORM\ManyToOne(targetEntity="Customer",cascade={"persist"})
     * @ORM\JoinColumn(name="customerId", referencedColumnName="id")
     */
    protected $customer;

   /**
   * @var Product[] $products
   * @ORM\OneToMany(targetEntity="Product",cascade={"persist"})
   */
   protected $products;  
 
    /**
     * @return int
    */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @param int $id
    */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return Customer
    */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }
    
    /**
     * @param Customer $customer
    */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Product[]
    */
    public function getProducts(): array
    {
        return $this->products;
    }
    
    /**
     * @param Product[] $products
    */
    public function setProducts(array $products)
    {
        $this->products = $products;
    }
}
```

 Above we defined an `OrderDto` for the `Order` Entity with two embedded objects
 `Customer` and `Products` one is related to `Order` by a `ManyToOne` relations ship 
 and the other is related to `Order` by a `OneToMany` relationship.
 
 ### GET Request
 
 ```
  GET https://domain.com/api/orders/{id}
 ```

The above request will go first through your custom dataProvider (if you have one) retrieving
the `Order` Entity to be transformed and normalized , if you defined an `Ouput` for your `@ApiResource`
then you should define a transformer your transformer would look like this :

```
use DtoHydrator\Factory\DtoDtoFactory;
use DtoHydrator\Dto\Hydrator;

class OrderDtoOutputTransformer {
  
    /** @var DtoFactory $factory */
    protected $factory;

    /**
     * OrderDtoOutputTransformer constructor.
     *
     * @param DtoFactory $factory
     */
    public function __construct(DtoFactory $factory) { $this->factory = $factory; }


    /**
     * Checks whether the transformation is supported for a given data and context.
     *
     * @param object|array $data object on normalize / array on denormalize
     * @param string       $to
     * @param array        $context
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return OrderDto::class === $to && $data instanceof Order;
    }

    /**
     * Transforms the given object to something else, usually another object.
     * This must return the original object if no transformation has been done.
     *
     * @param Order $order
     *
     * @param string          $to
     * @param array           $context
     * @return OrderDto
     */
    public function transform($order, string $to, array $context = []): OrderDto
    {
        $orderDto = new OrderDto();

        $this->factory->make(Hydrator::class)->hydrate($order,$orderDto,$context);

        return $orderDto;
    }

}
```

 ### PUT Request

 ```
  PUT https://domain.com/api/orders/{id} 
 
  body={
      id:1,
      customer:{
         id:1,
         fname:"Omar",
         lnaem:"Salamov"
      },
      products:[
        {
           id:10,
           name:"Long Sleeves",
           brand: "Gap"
        },
        {
          id:11,
          name:"Short Sleves",
          brand: "H&M"
        }
     ]
  }
 ```

The above request will be denormalized to the class mapped to your `Input` defined in the `@ApiResource`
then to your `InputTransformer` in order to be transformed to an `Entity` and persisted , so your `InputTransformer`
would look like this .

```
use DtoHydrator\Factory\DtoDtoFactory;
use DtoHydrator\Dto\Dehydrator;
use Doctrine\ORM\EntityManagerInterface;

class OrderDtoInputTransformer {
   
    /** @var DtoFactory $factory */
    protected $factory;

    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;

    /**
     * ShippingAddressInputTransformer constructor.
     *
     * @param DtoFactory                $factory
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(DtoFactory $factory, EntityManagerInterface $entityManager)
    {
        $this->factory       = $factory;
        $this->entityManager = $entityManager;
    }


    /**
     * Checks whether the transformation is supported for a given data and context.
     *
     * @param object|array $data object on normalize / array on denormalize
     * @param string       $to
     * @param array        $context
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return Order::class === $to && in_array('delivery:put',$context['groups']);
    }

    /**
     * Transforms the given object to something else, usually another object.
     * This must return the original object if no transformation has been done.
     *
     * @param OrderDto  $order
     * @param string    $to
     * @param array     $context
     * @return Order
     * @throws Throwable
     */
    public function transform($orderDto, string $to, array $context = []): Order
    {
        /** @var Order $order */
        $order = $this->entityManager->getReference(Order::class, $orderDto->getId());
        
        // in case you are creating a new order 
        // you will need to instantiate a new instance from order instead of referencing

        $this->factory->make(Dehydrator::class)->hydrate($orderDto,$order,$context);

        return $order;
    }
}  
```

**N.B :** If your body/embedded body contains an `id` it will be directly referenced to 
the corresponding entity for that `id` and the data will be updated , if not it will be created .


## Security

If you discover any security related issues, please email omarfawzi96@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
