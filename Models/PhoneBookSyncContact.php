<?php
/**
 * Cloud Phonebook v1.1.3
 * Model: PhoneBookSyncContact — externe contacten
 * Conform MikoPBX ModulesModelsBase vereisten
 */
namespace Modules\ModulePhoneBookSync\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class PhoneBookSyncContact extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @Column(type="string", nullable=false)
     */
    public $name;

    /**
     * @Column(type="string", nullable=false)
     */
    public $number;

    /**
     * @Column(type="string", nullable=true)
     */
    public $department;

    /**
     * @Column(type="string", nullable=true)
     */
    public $category;

    /**
     * @Column(type="string", nullable=true)
     */
    public $notes;

    /**
     * @Column(type="integer", nullable=true)
     */
    public $created_at;

    /**
     * @Column(type="integer", nullable=true)
     */
    public $updated_at;

    public function initialize(): void
    {
        $this->setSource('m_PhoneBookSyncContacts');
        parent::initialize();
        $this->useDynamicUpdate(true);
    }

    public function beforeCreate(): void
    {
        $this->created_at = time();
        $this->updated_at = time();
    }

    public function beforeUpdate(): void
    {
        $this->updated_at = time();
    }
}
