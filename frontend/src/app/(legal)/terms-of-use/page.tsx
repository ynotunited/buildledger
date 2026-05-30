import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Terms of Use | BuildLedger",
  description: "The terms and conditions governing your use of the BuildLedger platform.",
};

const LAST_UPDATED = "25 May 2026";

export default function TermsOfUsePage() {
  return (
    <article>
      <LegalHeader
        badge="Legal"
        title="Terms of Use"
        updated={LAST_UPDATED}
        summary="By accessing or using BuildLedger, you agree to be bound by these terms. Please read them carefully before using the platform."
      />

      <Section title="1. Acceptance of Terms">
        <p>
          These Terms of Use (&ldquo;Terms&rdquo;) constitute a legally binding agreement between you and
          <strong> Webxpress Technologies</strong> (&ldquo;Company&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;)
          governing your access to and use of the BuildLedger platform (&ldquo;Service&rdquo;).
        </p>
        <p>
          If you are using the Service on behalf of a business, you represent that you have authority to bind
          that business to these Terms.
        </p>
      </Section>

      <Section title="2. Eligibility">
        <p>You must be at least 18 years old and capable of entering into a binding contract to use BuildLedger. The Service is intended for business use only.</p>
      </Section>

      <Section title="3. Your Account">
        <ul>
          <li>You are responsible for maintaining the confidentiality of your login credentials.</li>
          <li>You are responsible for all activity that occurs under your account.</li>
          <li>You must notify us immediately at <a href="mailto:support@buildledger.com">support@buildledger.com</a> if you suspect unauthorised access.</li>
          <li>You may not share your account with others or create accounts on behalf of third parties without authorisation.</li>
        </ul>
      </Section>

      <Section title="4. Acceptable Use">
        <p>You agree not to use the Service to:</p>
        <ul>
          <li>Violate any applicable law or regulation.</li>
          <li>Transmit fraudulent, misleading, or deceptive content.</li>
          <li>Infringe the intellectual property rights of any third party.</li>
          <li>Distribute malware, spam, or other harmful code.</li>
          <li>Attempt to gain unauthorised access to any part of the Service or its infrastructure.</li>
          <li>Scrape, crawl, or systematically extract data from the platform without written permission.</li>
          <li>Resell or sublicense access to the Service without our written consent.</li>
        </ul>
      </Section>

      <Section title="5. Your Content">
        <p>
          You retain ownership of all data and content you upload to BuildLedger (&ldquo;Your Content&rdquo;).
          By using the Service, you grant us a limited, non-exclusive licence to store, process, and display
          Your Content solely to provide the Service to you.
        </p>
        <p>
          You are solely responsible for the accuracy, legality, and appropriateness of Your Content.
        </p>
      </Section>

      <Section title="6. Payments and Billing">
        <p>
          Certain features of BuildLedger may require payment. All fees are stated in Nigerian Naira (NGN)
          unless otherwise specified. Payments are processed by Paystack or Flutterwave and are subject to
          their respective terms.
        </p>
        <p>
          All fees are non-refundable except where required by applicable law or as expressly stated in a
          separate agreement.
        </p>
      </Section>

      <Section title="7. Intellectual Property">
        <p>
          The BuildLedger platform, including its design, code, trademarks, and content (excluding Your Content),
          is owned by Webxpress Technologies and protected by applicable intellectual property laws.
          You may not copy, modify, distribute, or create derivative works without our prior written consent.
        </p>
      </Section>

      <Section title="8. Disclaimers">
        <p>
          The Service is provided &ldquo;as is&rdquo; and &ldquo;as available&rdquo; without warranties of any kind,
          express or implied. We do not warrant that the Service will be uninterrupted, error-free, or free of
          viruses or other harmful components.
        </p>
      </Section>

      <Section title="9. Limitation of Liability">
        <p>
          To the maximum extent permitted by law, Webxpress Technologies shall not be liable for any indirect,
          incidental, special, consequential, or punitive damages arising from your use of or inability to use
          the Service, even if we have been advised of the possibility of such damages.
        </p>
        <p>
          Our total liability to you for any claim arising from these Terms shall not exceed the amount you
          paid us in the 12 months preceding the claim.
        </p>
      </Section>

      <Section title="10. Termination">
        <p>
          We may suspend or terminate your account at any time for violation of these Terms, with or without
          notice. You may terminate your account at any time by contacting us. Upon termination, your right
          to use the Service ceases immediately.
        </p>
      </Section>

      <Section title="11. Governing Law">
        <p>
          These Terms are governed by the laws of the Federal Republic of Nigeria. Any disputes shall be
          subject to the exclusive jurisdiction of the courts of Nigeria.
        </p>
      </Section>

      <Section title="12. Changes to These Terms">
        <p>
          We reserve the right to modify these Terms at any time. We will provide at least 14 days&apos; notice
          of material changes via email or in-app notification. Continued use after the effective date
          constitutes acceptance of the revised Terms.
        </p>
      </Section>

      <Section title="13. Contact">
        <p>
          For questions about these Terms, contact us at{" "}
          <a href="mailto:legal@buildledger.com">legal@buildledger.com</a>.
        </p>
      </Section>
    </article>
  );
}

function LegalHeader({
  badge, title, updated, summary,
}: { badge: string; title: string; updated: string; summary: string }) {
  return (
    <div className="mb-14 border-b border-white/8 pb-10">
      <span className="inline-block rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-medium uppercase tracking-widest text-emerald-300">
        {badge}
      </span>
      <h1 className="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">{title}</h1>
      <p className="mt-4 text-sm text-zinc-500">Last updated: {updated}</p>
      <p className="mt-5 max-w-2xl text-base leading-7 text-zinc-300">{summary}</p>
    </div>
  );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="mb-10">
      <h2 className="mb-4 text-xl font-semibold text-white">{title}</h2>
      <div className="space-y-3 text-sm leading-7 text-zinc-400">{children}</div>
    </section>
  );
}
