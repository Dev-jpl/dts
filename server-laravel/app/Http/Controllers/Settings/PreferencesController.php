<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Models\UserNotificationPreference;
use Illuminate\Http\Request;

class PreferencesController extends Controller
{
    /**
     * Get user preferences
     * GET /api/settings/preferences
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        // Return defaults if no preferences set
        if (!$preferences) {
            $preferences = [
                'date_format' => 'Y-m-d',
                'timezone' => 'Asia/Manila',
                'default_period' => 'month',
                'dashboard_realtime' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update user preferences
     * PUT /api/settings/preferences
     */
    public function update(Request $request)
    {
        $request->validate([
            'date_format' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'default_period' => 'nullable|in:week,month,quarter,year',
            'dashboard_realtime' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'date_format' => $request->input('date_format', 'Y-m-d'),
                'timezone' => $request->input('timezone', 'Asia/Manila'),
                'default_period' => $request->input('default_period', 'month'),
                'dashboard_realtime' => $request->boolean('dashboard_realtime', true),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.',
            'data' => $preferences,
        ]);
    }

    /**
     * Get notification preferences
     * GET /api/notifications/preferences
     */
    public function notificationPreferences(Request $request)
    {
        $user = $request->user();
        $eventTypes = UserNotificationPreference::eventTypes();
        $userPrefs = $user->notificationPreferences->keyBy('event_type');

        $data = [];
        foreach ($eventTypes as $type => $label) {
            $pref = $userPrefs->get($type);
            $data[] = [
                'event_type' => $type,
                'label' => $label,
                'in_app' => $pref ? $pref->in_app : true,
                'email' => $pref ? $pref->email : false,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update notification preferences
     * PUT /api/notifications/preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.event_type' => 'required|string',
            'preferences.*.in_app' => 'required|boolean',
            'preferences.*.email' => 'required|boolean',
        ]);

        $user = $request->user();
        $eventTypes = array_keys(UserNotificationPreference::eventTypes());

        foreach ($request->input('preferences') as $pref) {
            if (!in_array($pref['event_type'], $eventTypes)) {
                continue;
            }

            UserNotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_type' => $pref['event_type'],
                ],
                [
                    'in_app' => $pref['in_app'],
                    'email' => $pref['email'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully.',
        ]);
    }
}
