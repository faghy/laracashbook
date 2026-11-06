<?php

namespace App\View\Components;

use Illuminate\View\Component;

class EntityChoice extends Component
{
    public $entities;

    /**
     * Create a new component instance.
     *
     * @param array $entities
     * @return void
     */
    public function __construct($entities)
    {
        $this->entities = $entities;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.entity-choice');
    }
}
