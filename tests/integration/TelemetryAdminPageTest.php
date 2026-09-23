<?php

namespace Risistar\Tests\Integration;

use Language;
use TelemetryConnection;
use TelemetryPresentation;
use TelemetryReview;
use TelemetrySettings;
use TelemetryStore;

require_once __DIR__ . '/TelemetryTestCase.php';

/**
 * Integration tests for the moderator page: forms, audit trail and account names.
 */
class TelemetryAdminPageTest extends TelemetryTestCase
{
    private const MODERATOR_ID = 1;

    private TelemetryReview $review;
    private int $lastAuditId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$db === null) {
            return;
        }

        require_once self::rootPath() . 'includes/classes/TelemetryPresentation.class.php';
        require_once self::rootPath() . 'includes/pages/adm/ShowTelemetryPage.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['LNG'] = new Language('en');
        $GLOBALS['LNG']->includeData(['L18N', 'INGAME', 'ADMIN', 'CUSTOM']);
        $GLOBALS['USER'] = ['id' => self::MODERATOR_ID];
        $_POST = [];
        $this->review = new TelemetryReview($this->store);
        $this->lastAuditId = (int) $this->store->query('SELECT COALESCE(MAX(id), 0) FROM %%TELEMETRY_AUDIT%%')->fetchColumn();
    }

    protected function tearDown(): void
    {
        if (isset($this->store)) {
            $this->store->query('DELETE FROM %%TELEMETRY_AUDIT%% WHERE id > ?', [$this->lastAuditId]);
        }
        $_POST = [];

        parent::tearDown();
    }

    public function testSettingsFormRejectsAForgedSessionToken(): void
    {
        $_POST = ['sid' => 'forged', 'action' => 'settings', 'settings' => $this->formValues(['timing_share' => 95])];

        try {
            telemetryHandlePost($this->review, 1, 'session-token', time());
            $this->fail('A forged token was accepted.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($GLOBALS['LNG']['telemetry_expired'], $e->getMessage());
        }

        $this->assertSame(0.8, TelemetrySettings::get(1)['timing_share']);
    }

    public function testSettingsFormStoresPercentagesAsFractions(): void
    {
        $_POST = ['sid' => 'session-token', 'action' => 'settings', 'settings' => $this->formValues(['timing_share' => 85])];

        telemetryHandlePost($this->review, 1, 'session-token', time());

        $this->assertSame(0.85, TelemetrySettings::get(1)['timing_share']);

        $history = telemetrySettingsHistory($this->store, new TelemetryPresentation(new \DateTimeZone('UTC')), 1);
        $this->assertSame('80 % → 85 %', $history[0]['changes'][0]['value']);
    }

    public function testSettingsStayUnchangedWhenTheirAuditCannotBeSaved(): void
    {
        $store = new class (TelemetryConnection::open()) extends TelemetryStore {
            public function query(string $sql, array $values = []): \PDOStatement
            {
                if (str_contains($sql, 'INSERT INTO %%TELEMETRY_AUDIT%%')) {
                    throw new \RuntimeException('Audit table unavailable');
                }
                return parent::query($sql, $values);
            }
        };
        $_POST = ['sid' => 'session-token', 'action' => 'settings', 'settings' => $this->formValues(['timing_share' => 95])];

        try {
            telemetryHandlePost(new TelemetryReview($store), 1, 'session-token', time());
            $this->fail('The settings were saved without their audit entry.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit table unavailable', $e->getMessage());
        }

        $this->assertSame(0.8, TelemetrySettings::get(1)['timing_share']);
    }

    public function testModeratorDecisionIsSavedWithItsNote(): void
    {
        $now = time();
        $this->store->write([$this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        $this->review->evaluate(1, $this->settings, $now, 900000, 900000);
        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001')->fetchColumn();
        $_POST = ['sid' => 'session-token', 'action' => 'review', 'id' => $id, 'status' => 'dismissed', 'note' => 'Alliance war loan'];

        telemetryHandlePost($this->review, 1, 'session-token', $now);

        $case = $this->review->evidence(1, $id);
        $this->assertSame('dismissed', $case['status']);
        $decision = json_decode($case['review_history'][0]['data'], true);
        $this->assertSame(self::MODERATOR_ID, (int) $case['review_history'][0]['admin']);
        // MySQL JSON columns do not keep the key order.
        $this->assertEquals(['before' => 'open', 'after' => 'dismissed', 'note' => 'Alliance war loan'], $decision);
    }

    public function testPushingCaseShowsTheAllTimeExchange(): void
    {
        $now = time();
        $this->store->write([
            $this->event(900002, $now - 60 * 86400, 'delivery', 900001, ['deuterium' => 500000]),
            $this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000]),
        ]);
        $this->review->evaluate(1, $this->settings, $now, 900000, 900000);
        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001')->fetchColumn();

        $case = telemetryCase($this->review, new TelemetryPresentation(new \DateTimeZone('UTC')), 1, $id);

        $rows = array_column($case['evaluations'][0]['exchange']['resources'], null, 'label');
        $this->assertSame(pretty_number(4000000), $rows['All-time exchange / Resources sent']['metal']);
        $this->assertSame(pretty_number(500000), $rows['All-time exchange / Resources received in return']['deuterium']);
    }

    public function testDeletedAccountsStayReadable(): void
    {
        $names = telemetryNames(1, [900001]);

        $this->assertSame($GLOBALS['LNG']['telemetry_deleted_account'] . ' (900001)', $names[900001]);
    }

    /** Values as the browser sends them: percentages, not fractions. */
    private function formValues(array $changes): array
    {
        $values = [];
        foreach (telemetrySettingFields(TelemetrySettings::get(1)) as $fields) {
            foreach ($fields as $field) {
                $values[$field['key']] = $field['value'];
            }
        }

        return array_replace($values, $changes);
    }
}
