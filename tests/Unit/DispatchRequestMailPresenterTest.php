<?php

namespace Tests\Unit;

use App\Services\DispatchRequests\DispatchRequestMailPresenter;
use Tests\TestCase;

class DispatchRequestMailPresenterTest extends TestCase
{
    public function test_parse_reference_code_search_id_full_format(): void
    {
        $this->assertSame(42, DispatchRequestMailPresenter::parseReferenceCodeSearchId('REQ-202506-042'));
        $this->assertSame(42, DispatchRequestMailPresenter::parseReferenceCodeSearchId('req-202506-042'));
    }

    public function test_parse_reference_code_search_id_legacy_format(): void
    {
        $this->assertSame(123, DispatchRequestMailPresenter::parseReferenceCodeSearchId('REQ-123'));
        $this->assertSame(123, DispatchRequestMailPresenter::parseReferenceCodeSearchId('REQ123'));
    }

    public function test_parse_reference_code_search_id_returns_null_for_non_code(): void
    {
        $this->assertNull(DispatchRequestMailPresenter::parseReferenceCodeSearchId('Hà Nội'));
        $this->assertNull(DispatchRequestMailPresenter::parseReferenceCodeSearchId(''));
    }
}
