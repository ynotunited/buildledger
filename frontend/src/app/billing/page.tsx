"use client";

import { useEffect, useTransition, useState } from "react";
import AppShell from "@/components/layout/AppShell";
import axiosInstance from "@/lib/axios";
import { getOrCreateIdempotencyKey } from "@/lib/idempotency";

type Plan = {
  id: number;
  code: string;
  name: string;
  description: string;
  price_ngn: number;
  price_annually_ngn: number | null;
  billing_interval: string;
  features: string[];
};

type BillingState = {
  plans: { data?: Plan[] } | Plan[];
  current_plan: Plan | null;
  subscription: {
    status: string;
    billing_interval: string | null;
    current_period_ends_at: string | null;
    cancelled_at: string | null;
    expires_at: string | null;
  } | null;
  trial_ends_at: string | null;
};

export default function BillingPage() {
  const [state, setState] = useState<BillingState | null>(null);
  const [loading, setLoading] = useState(true);
  const [gateway, setGateway] = useState<"paystack" | "flutterwave">("paystack");
  const [billingInterval, setBillingInterval] = useState<"monthly" | "annual">("monthly");
  const [processingPlan, setProcessingPlan] = useState<string | null>(null);
  const [, startTransition] = useTransition();

  const loadBilling = async () => {
    try {
      const response = await axiosInstance.get("/billing");
      setState(response.data);
    } catch (error) {
      console.error("Error loading billing", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    startTransition(() => {
      void loadBilling();
    });
  }, []);

  const plans = Array.isArray(state?.plans) ? state.plans : state?.plans?.data ?? [];
  const paidPlans = plans.filter((plan) => plan.price_ngn > 0);

  const beginCheckout = async (planCode: string) => {
    setProcessingPlan(planCode);
    try {
      const idempotencyKey = getOrCreateIdempotencyKey("billing-checkout", {
        planCode,
        gateway,
        billingInterval,
      });

      const response = await axiosInstance.post(
        "/billing/checkout",
        {
          plan_code: planCode,
          gateway,
          billing_interval: billingInterval,
          idempotency_key: idempotencyKey,
        },
        {
          headers: {
            "Idempotency-Key": idempotencyKey,
          },
        }
      );

      if (response.data.authorization_url) {
        window.location.assign(response.data.authorization_url);
        return;
      }

      if (response.data.idempotency_status === "processing") {
        window.alert("Your checkout request is still processing. Please retry the same action in a moment.");
        return;
      }

      await loadBilling();
    } catch (error) {
      console.error("Error starting checkout", error);
    } finally {
      setProcessingPlan(null);
    }
  };

  const cancelSubscription = async () => {
    try {
      await axiosInstance.post("/billing/cancel");
      await loadBilling();
    } catch (error) {
      console.error("Error cancelling subscription", error);
    }
  };

  return (
    <AppShell>
      <div className="space-y-8">
        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <h1 className="text-3xl font-semibold tracking-tight">Billing & paywall</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            Manage your subscription, compare the paid ladder, and route plan payments through your existing gateways.
          </p>
          {state?.current_plan ? (
            <div className="mt-5 rounded-2xl border border-white/10 bg-background/60 px-4 py-3 text-sm">
              Current plan: <span className="font-semibold">{state.current_plan.name}</span>
              {state.subscription?.current_period_ends_at ? (
                <span className="text-muted-foreground"> · Renews until {new Date(state.subscription.current_period_ends_at).toLocaleDateString()}</span>
              ) : null}
              {state.subscription?.status === "cancelled" ? (
                <span className="text-muted-foreground"> · Cancelled</span>
              ) : null}
              {state.subscription?.expires_at ? (
                <span className="text-muted-foreground"> · Access ends {new Date(state.subscription.expires_at).toLocaleDateString()}</span>
              ) : null}
              {state.current_plan.code === "starter" && state.trial_ends_at ? (
                <span className="text-muted-foreground"> · Free trial ends {new Date(state.trial_ends_at).toLocaleDateString()}</span>
              ) : null}
            </div>
          ) : null}
        </section>

        <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
          <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="text-xl font-semibold">Choose payment gateway</h2>
              <p className="mt-1 text-sm text-muted-foreground">Your invite-only trial stays separate; the cards below are the paid plans.</p>
            </div>
            <div className="flex flex-col gap-3 md:flex-row">
              <select
                value={billingInterval}
                onChange={(event) => setBillingInterval(event.target.value as "monthly" | "annual")}
                className="rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              >
                <option value="monthly">Monthly billing</option>
                <option value="annual">Annual billing</option>
              </select>
              <select
                value={gateway}
                onChange={(event) => setGateway(event.target.value as "paystack" | "flutterwave")}
                className="rounded-2xl border border-border bg-background px-4 py-3 text-sm"
              >
                <option value="paystack">Paystack</option>
                <option value="flutterwave">Flutterwave</option>
              </select>
            </div>
          </div>
        </section>

        {loading ? (
          <div className="rounded-[2rem] border border-white/10 bg-card p-6 text-sm text-muted-foreground">Loading plans...</div>
        ) : (
          <section className="grid gap-4 lg:grid-cols-2">
            {paidPlans.map((plan) => {
              const isCurrentPlan = state?.current_plan?.code === plan.code;
              const currentBillingInterval = state?.subscription?.billing_interval ?? null;
              const isActiveWithSameInterval =
                state?.subscription?.status === "active" &&
                isCurrentPlan &&
                currentBillingInterval === billingInterval;
              const displayPrice = billingInterval === "annual"
                ? (plan.price_annually_ngn ?? plan.price_ngn * 12)
                : plan.price_ngn;
              const displayPeriod = billingInterval === "annual" ? "year" : "month";
              return (
                <div key={plan.code} className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-2xl font-semibold">{plan.name}</h3>
                      <p className="mt-2 text-sm text-muted-foreground">{plan.description}</p>
                    </div>
                    {isCurrentPlan && currentBillingInterval === billingInterval ? (
                      <span className="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">
                        Current
                      </span>
                    ) : null}
                  </div>
                  <p className="mt-6 text-4xl font-semibold">
                    {`₦${displayPrice.toLocaleString("en-NG")}`}
                    <span className="ml-2 text-sm font-normal text-muted-foreground">/{displayPeriod}</span>
                  </p>
                  <div className="mt-6 space-y-2">
                    {plan.features.map((feature) => (
                      <div key={feature} className="rounded-2xl border border-border bg-background/70 px-4 py-3 text-sm">
                        {feature.replace(/_/g, " ")}
                      </div>
                    ))}
                  </div>
                  <button
                    onClick={() => void beginCheckout(plan.code)}
                    disabled={processingPlan === plan.code || isActiveWithSameInterval}
                    className="mt-6 inline-flex rounded-2xl bg-primary px-5 py-3 text-sm font-medium text-primary-foreground disabled:opacity-60"
                  >
                    {processingPlan === plan.code
                      ? "Processing..."
                      : isActiveWithSameInterval
                        ? "Active plan"
                        : isCurrentPlan && currentBillingInterval !== billingInterval
                          ? `Switch to ${billingInterval === "annual" ? "annual" : "monthly"} billing`
                          : "Upgrade now"}
                  </button>
                </div>
              );
            })}
          </section>
        )}

        {!loading && !state?.current_plan ? (
          <section className="rounded-[2rem] border border-amber-500/20 bg-amber-500/10 p-6">
            <h2 className="text-xl font-semibold text-amber-100">No active plan</h2>
            <p className="mt-2 text-sm text-amber-100/80">
              Your free trial has ended or your subscription is inactive. Choose a paid plan to restore access.
            </p>
          </section>
        ) : null}

        {state?.subscription?.status === "active" && state.current_plan?.code !== "starter" ? (
          <section className="rounded-[2rem] border border-white/10 bg-card p-6 md:p-8">
            <h2 className="text-xl font-semibold">Manage subscription</h2>
            <p className="mt-2 text-sm text-muted-foreground">
              You can cancel now to stop the next renewal. Renewals are now automated through your gateway, so cancellation simply prevents the next cycle from charging.
            </p>
            <button
              onClick={() => void cancelSubscription()}
              className="mt-5 inline-flex rounded-2xl border border-red-500/20 bg-red-500/10 px-5 py-3 text-sm font-medium text-red-300"
            >
              Cancel subscription
            </button>
          </section>
        ) : null}
      </div>
    </AppShell>
  );
}
