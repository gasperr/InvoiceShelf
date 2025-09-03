@component('mail::layout')

@slot('header')
@endslot

@slot('subcopy')
@endslot

@slot('footer')
@endslot


@if (!empty($data['body']) && trim($data['body']) !== '<p></p>')
{{-- Body exists --}}
{!! $data['body'] !!}
@else
{{-- Nothing available --}}
<p>Pozdravljeni,</p>

<p>v priponki račun za pretekli mesec.</p>

<p>Hvala in lep pozdrav,</p>
@endif


<div style="width: 71ch;">
  <table cellspacing="5" cellpadding="10" border="0" valign="top">
    <tr>
      <td align="left" valign="top">
        <a href="http://www.tibaran.com/" target="_blank">
          <img src="https://portal.adverts-tracker.com/images/tibaran_logo.png"
               style="display:inline;" width="90" height="90" border="0">
        </a>
      </td>
      <td align="left" valign="top" width="70%">
        <table cellspacing="0" cellpadding="0" border="0" valign="top">
          <tr>
            <td style="color: #1E1E1E; font-size: 14px; font-weight:600; font-family:monospace;">
              <span style="margin-right: 50px;">Gašper Andrejc</span>
            </td>
          </tr>
          <tr>
            <td style="color: #9C9C9C; font-size: 11px; font-family:monospace; line-height:120%; width:100%; padding-top:5px; padding-bottom:5px;">
              Tibaran | SI69824509<br>
              Parižlje 117, 3314 Braslovče, Slovenia
            </td>
          </tr>
          <tr>
            <td style="font-size: 11px; font-family: monospace; line-height: 120%; width: 100%; padding-bottom: 10px;">
              <a href="mailto:gasper.andrejc@tibaran.com" style="color: #1E1E1E; text-decoration:none;">
                gasper.andrejc@tibaran.com
              </a>
              <a href="tel:+38641551431" style="color: #9C9C9C; text-decoration:none;">| +386 41 551 431</a>
            </td>
          </tr>
          <tr>
            <td>
              <table cellspacing="0" cellpadding="1" border="0" valign="top" width="100">
                <tr>
                  <td align="left" valign="bottom">
                    <a href="https://www.linkedin.com/in/andrejcgasper/" target="_blank" style="text-decoration:none;">
                      <img src="https://portal.adverts-tracker.com/images/ln_logo.png" width="16" height="16"
                           style="display:inline-block;" border="0">
                    </a>
                  </td>
                  <td align="left" valign="bottom">
                    <a href="http://www.tibaran.com" target="_blank">
                      <img src="https://portal.adverts-tracker.com/images/globe.png" width="16" height="16"
                           style="display:inline-block;" border="0">
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>

@endcomponent
