<?php
/**
 * Hook Loader
 *
 * Maintains a list of all hooks that are registered throughout
 * the plugin and registers them with WordPress.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Loader {

    /**
     * Array of actions to register
     * @var array
     */
    protected $actions = array();

    /**
     * Array of filters to register
     * @var array
     */
    protected $filters = array();

    /**
     * Add a new action to the collection
     *
     * @param string $hook          WordPress hook name
     * @param object $component     Object with the callback method
     * @param string $callback      Method name
     * @param int    $priority      Hook priority
     * @param int    $accepted_args Number of arguments
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection
     *
     * @param string $hook          WordPress hook name
     * @param object $component     Object with the callback method
     * @param string $callback      Method name
     * @param int    $priority      Hook priority
     * @param int    $accepted_args Number of arguments
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add hook to collection
     *
     * @param array  $hooks
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int    $priority
     * @param int    $accepted_args
     * @return array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Register all hooks with WordPress
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
