<?php
namespace watoki\stepper;
 
interface Step {

    /**
     * @return Step|null Return the next step or null if this was the last step
     */
    public function next();

    /**
     * Executes this step
     * @return void
     */
    public function up();

    /**
     * Undoes this step (if possible)
     * @return void
     */
    public function down();

    /**
     * @return boolean
     */
    public function canBeUndone();

}
