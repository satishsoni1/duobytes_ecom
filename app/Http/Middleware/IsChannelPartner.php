<?php

namespace App\Http\Middleware;

use App\Models\ChannelPartner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsChannelPartner
{
    /**
     * Allows only channel partner accounts. Pass "approved" to also require an
     * approved, non-expired partnership (catalog, cart and ordering routes).
     */
    public function handle(Request $request, Closure $next, ?string $requirement = null)
    {
        if (!config('channel_partner.enabled')) {
            abort(404);
        }

        $user = Auth::user();

        if (!$user) {
            return redirect()->guest(route('channel_partner.login'));
        }

        if ($user->user_type !== 'channel_partner') {
            abort(403, translate('This area is only for channel partners.'));
        }

        if ($user->banned) {
            Auth::logout();
            $request->session()->invalidate();
            flash(translate('Your partner account has been disabled. Please contact us.'))->error();
            return redirect()->route('channel_partner.login');
        }

        $partner = $user->channelPartner ?? ChannelPartner::create(['user_id' => $user->id]);
        $request->attributes->set('channel_partner', $partner);

        if ($requirement === 'approved') {
            if (!$partner->isApproved()) {
                return redirect()->route('channel_partner.onboarding');
            }
            if ($partner->contractExpired() && !$request->routeIs('channel_partner.orders.*', 'channel_partner.dashboard')) {
                flash(translate('Your partner contract has expired. Please contact your account manager to renew it.'))->warning();
                return redirect()->route('channel_partner.dashboard');
            }
        }

        return $next($request);
    }
}
