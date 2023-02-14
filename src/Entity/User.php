<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Since;

use Hateoas\Configuration\Annotation as Hateoas;

/**
 * 
 * 
 * @Hateoas\Relation(
 *      "detail",
 *      href = "expr('/api/customer/' ~ object.getIdCustomer().getId() ~ '/user/' ~ object.getId())",
 *      exclusion = @Hateoas\Exclusion(groups="getAllUsers")
 * )
 * 
 * 
 * @Hateoas\Relation(
 *      "delete",
 *      href = "expr('/api/customer/' ~ object.getIdCustomer().getId() ~ '/user/' ~ object.getId())",
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerUsers")
 * )
 * 
 * 
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 */

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getCustomerUsers", "getAllUsers"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    #[Since("2.0")]
    private ?string $city = null;

    #[ORM\Column(length:255, nullable: true)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    #[Since("2.0")]
    private ?string $address = null;

    #[ORM\Column(nullable: true)]
    #[Groups(["getCustomerUsers", "addUser", "getAllUsers"])]
    #[Since("2.0")]
    private ?int $zipCode = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Customer $id_customer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    public function setZipCode(?int $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getIdCustomer(): ?Customer
    {
        return $this->id_customer;
    }

    public function setIdCustomer(?Customer $id_customer): self
    {
        $this->id_customer = $id_customer;

        return $this;
    }
}
