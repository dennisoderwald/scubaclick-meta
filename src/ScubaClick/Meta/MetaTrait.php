<?php namespace ScubaClick\Meta;

use ScubaClick\Meta\Helpers;
use Illuminate\Support\Collection;

trait MetaTrait
{
    /**
     * Gets all meta data
     *
     * @return Illuminate\Support\Collection
     */
    public function getAllMeta()
    {
        return new Collection($this->meta->lists('value', 'key'));
    }

    /**
     * Gets meta data
     *
     * @return Illuminate\Support\Collection
     */
    public function getMeta($key, $getObj = false)
    {
        $meta = $this->meta()
            ->where('key', $key)
            ->get();

        if ($getObj) {
            $collection = $meta;

        } else {
            $collection = new Collection();

            foreach ($meta as $m) {
                $collection->put($m->id, $m->value);
            }
        }

        return $collection->count() <= 1 ? $collection->first() : $collection;
    }

    /**
     * Updates meta data
     *
     * @return mixed
     */
    public function updateMeta($key, $newValue, $oldValue = false)
    {
        $meta = $this->getMeta($key, true);

        if ($meta == null) {
            return $this->addMeta($key, $newValue);
        }

        $obj = $this->getEditableItem($meta, $oldValue);

        if ($obj !== false) {
            $meta = $obj->update([
                'value' => $newValue
            ]);

            return $meta->isSaved() ? $meta : $meta->getErrors();
        }
    }

    /**
     * Adds meta data
     *
     * @return mixed
     */
    public function addMeta($key, $value)
    {
        $existing = $this->meta()
            ->where('key', $key)
            ->where('value', Helpers::maybeEncode($value))
            ->first();

        if ($existing) {
            return false;
        }

        $meta = $this->meta()->create([
            'key'   => $key,
            'value' => $value,
        ]);

        return $meta->isSaved() ? $meta : $meta->getErrors();
    }

    /**
     * Appends a value to an existing meta entry
     * Resets all keys
     *
     * @return mixed
     */
    public function appendMeta($key, $value)
    {
        $meta = $this->getMeta($key);

        if(!$meta) {
            $meta = [];
        }

        if(is_array($value)) {
            $meta = array_merge($meta, $value);
        } else {
            $meta[] = $value;
        }

        return $this->updateMeta($key, array_values(array_unique($meta)));
    }

    /**
     * Deletes meta data
     *
     * @return mixed
     */
    public function deleteMeta($key, $value = false)
    {
        if ($value) {
            $meta = $this->getMeta($key, true);

            if ($meta == null) {
                return false;
            }

            $obj = $this->getEditableItem($meta, $value);

            return $obj !== false ? $obj->delete() : false;

        } else {
            return $this->meta()
                ->where('key', $key)
                ->delete();
        }
    }

    /**
     * Deletes all meta data
     *
     * @return mixed
     */
    public function deleteAllMeta()
    {
        return $this->meta()->delete();
    }

    /**
     * Gets an item to edit
     *
     * @return mixed
     */
    protected function getEditableItem($meta, $value)
    {
        if ($meta instanceof Collection) {
            if ($value === false) {
                return false;
            }

            $filtered = $meta->filter(function($m) use ($value) {
                return $m->value == $value;
            });

            $obj = $filtered->first();

            if ($obj == null) {
                return false;
            }
        } else {
            $obj = $meta;
        }

        return $obj->exists ? $obj : false;
    }

    /**
     * Attaches meta data
     *
     * @return object
     */
    public function meta()
    {
        return $this->morphMany('\\ScubaClick\\Meta\\Meta', 'metable');
    }
}
