import { describe, it } from 'node:test'
import assert from 'node:assert/strict'
import {
  parseMoneyVnd,
  formatMoneyDraftDisplay,
  formatVndWhileTyping,
  vndAmountInWords,
} from './money.js'

describe('parseMoneyVnd (VN thousand separators)', () => {
  it('parses single thousand group', () => {
    assert.equal(parseMoneyVnd('200.000'), 200_000)
    assert.equal(parseMoneyVnd('999.999'), 999_999)
  })

  it('parses multiple thousand groups', () => {
    assert.equal(parseMoneyVnd('1.000.000'), 1_000_000)
    assert.equal(parseMoneyVnd('1.500.000'), 1_500_000)
  })

  it('parses four digits before one .000 group', () => {
    assert.equal(parseMoneyVnd('1000.000'), 1_000_000)
  })

  it('keeps western decimals for non-VN fractions', () => {
    assert.equal(parseMoneyVnd('1234.56'), 1235)
  })

  it('accepts plain digits and numbers', () => {
    assert.equal(parseMoneyVnd('200000'), 200_000)
    assert.equal(parseMoneyVnd(200_000), 200_000)
  })
})

describe('formatMoneyDraftDisplay round-trip', () => {
  it('does not shrink 200.000 to 200 on blur path', () => {
    assert.equal(formatMoneyDraftDisplay('200.000'), '200.000')
    assert.equal(parseMoneyVnd(formatMoneyDraftDisplay('200.000')), 200_000)
  })

  it('formats while typing', () => {
    assert.equal(formatVndWhileTyping('200000'), '200.000')
  })
})

describe('vndAmountInWords', () => {
  it('reads grouped amounts correctly', () => {
    assert.match(vndAmountInWords('200.000'), /đồng$/)
  })
})
