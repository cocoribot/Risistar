<?php

final class TelemetrySchema
{
    /** The dedicated schema is installed manually; migration only verifies it. */
    public static function verify(): void
    {
        $config = TelemetryConnection::configuration();
        if (empty($config['databasename']) && empty($config['enabled'])) {
            return;
        }
        $db = TelemetryConnection::open();
        foreach ([
            'telemetry_daily'=>'universe,actor,day,windows',
            'telemetry_events'=>'event_key,request_id,universe,actor,target,pair_a,pair_b,at,kind,result,fleet_id,interactive,ip,data',
            'telemetry_warnings'=>'universe,actor,other,kind,strength,explanation,first_seen,last_seen,observation_start,observation_end,status,settings,evidence,latest_settings,latest_evidence',
            'telemetry_audit'=>'universe,admin,at,warning_id,action,data',
        ] as $table=>$columns) {
            $db->query("SELECT $columns FROM $table LIMIT 0")->closeCursor();
        }
    }
}
