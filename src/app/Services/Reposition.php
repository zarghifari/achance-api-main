<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class Reposition
{
    public static function UpdatePositions(Model $model, $lesson_id, $data)
    {
        $max_position = $model::where('lesson_id', $lesson_id)->max('position');
        if ($data['position'] == null) {
            $data['position'] = $model->position;
        }

        if ($data['position'] > $max_position + 1) {
            $data['position'] = $max_position + 1;
        }

        if ($data['position'] < 1) {
            $data['position'] = 1;
            $materials = $model::where('lesson_id', $lesson_id)
                ->where('position', '>=', 1)
                ->get();
            foreach ($materials as $m) {
                $m->position += 1;
                $m->save();
            }
        }

        if ($data['position'] < $model->position) {
            $materials = $model::where('lesson_id', $lesson_id)
                ->where('position', '>=', $data['position'])
                ->where('position', '<', $model->position)
                ->get();
            foreach ($materials as $m) {
                $m->position += 1;
                $m->save();
            }
        }

        if ($data['position'] > $model->position) {
            $materials = $model::where('lesson_id', $lesson_id)
                ->where('position', '>', $model->position)
                ->where('position', '<=', $data['position'])
                ->get();
            foreach ($materials as $m) {
                $m->position -= 1;
                $m->save();
            }
        }
    }
}