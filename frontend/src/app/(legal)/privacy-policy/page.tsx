import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Privacy Policy | BuildLedger",
  description: "How BuildLedger collects, uses, and protects your personal data.",
};

const LAST_UPDATED = "03 June 2026";

export default function PrivacyPolicyPage() {
  return (
    <article className="prose-legal">
      <LegalHeader
        badge="Legal"
        title="Privacy Policy"
        updated={LAST_UPDATED}
        summary="This policy explains what data BuildLedger collects, why we collect it, how we use it, and the rights you have over it."
      />

      <Section title="1. Who We Are">
        <p>
          BuildLedger is a business management platform operated by <strong>Webxpress Technologies</strong>
          (&ldquo;we&rdquo;, &ldquo;us&rdquo;, &ldquo;our&rdquo;). We provide tools for digital agencies and
          freelancers to manage clients, proposals, contracts, invoices, projects, and payments.
        </p>
        <p>
          For questions about this policy, contact us at{" "}
          <a href="mailto:privacy@buildledger.com">privacy@buildledger.com</a>.
        </p>
      </Section>

      <Section title="2. Data We Collect">
        <SubSection title="2.1 Account Data">
          <p>When you register, we collect your name, email address, and a hashed password. We never store your password in plain text.</p>
        </SubSection>
        <SubSection title="2.2 Business Data">
          <p>Data you enter into the platform — client records, proposals, contracts, invoices, project details, and payment records — is stored on your behalf and belongs to you.</p>
        </SubSection>
        <SubSection title="2.3 Usage Data">
          <p>We collect standard server logs including IP addresses, browser type, pages visited, and timestamps. This data is used solely for security monitoring and performance optimisation.</p>
        </SubSection>
        <SubSection title="2.4 Payment Data">
          <p>Payment processing is handled by Paystack and Flutterwave. We do not store card numbers or bank account details. We receive only transaction references and status confirmations from these gateways.</p>
        </SubSection>
        <SubSection title="2.5 Cookies">
          <p>We use strictly necessary cookies for session management. We do not use advertising or tracking cookies.</p>
        </SubSection>
        <SubSection title="2.6 Google Sign-In Data">
          <p>
            If you choose to sign in with Google, we receive only the information required to authenticate your
            account through Google Sign-In: your Google account email address, your display name, and Google&apos;s
            unique account identifier for that user. We do not request or access your Google contacts, calendar,
            Drive files, Gmail content, or other Google services.
          </p>
        </SubSection>
      </Section>

      <Section title="3. How We Use Your Data">
        <ul>
          <li>To provide, maintain, and improve the BuildLedger platform.</li>
          <li>To send transactional emails (invoice notifications, contract signing alerts, payment confirmations).</li>
          <li>To detect and prevent fraud, abuse, and security incidents.</li>
          <li>To comply with legal obligations.</li>
          <li>To create, sign in, verify, and link your BuildLedger account when you use Google Sign-In.</li>
        </ul>
        <p>We do not sell your data to third parties. We do not use your business data to train AI models.</p>
        <p>
          Google user data accessed through Sign-In is used only for authentication, account creation or linking,
          invite eligibility, and account security. We do not use Google user data for advertising, profiling, or
          unrelated secondary purposes.
        </p>
      </Section>

      <Section title="4. Data Sharing">
        <p>We share data only with the following categories of third parties, and only to the extent necessary:</p>
        <ul>
          <li><strong>Google OAuth services</strong> — to authenticate your Google account and verify your sign-in token.</li>
          <li><strong>Payment processors</strong> — Paystack, Flutterwave (for payment initiation and verification).</li>
          <li><strong>Cloud infrastructure</strong> — hosting providers for servers and file storage.</li>
          <li><strong>Email delivery</strong> — transactional email providers for notification delivery.</li>
          <li><strong>Legal authorities</strong> — when required by applicable law or court order.</li>
        </ul>
      </Section>

      <Section title="5. Data Retention">
        <p>
          We retain personal data only for as long as is reasonably necessary to fulfil the purposes for which it
          was collected, or for as long as retention is required or permitted by applicable law, regulation, tax,
          accounting, fraud prevention, dispute resolution, or security obligations. In ordinary operation,
          account-related data is retained while the account remains active.
        </p>
        <p>
          Where you request deletion or close your account, we will delete or anonymise personal data from active
          systems within 30 days of confirming the request, except where we are required or permitted to retain
          certain records for legal, regulatory, tax, accounting, or security purposes. In such cases, we retain
          only the minimum data necessary and restrict access to it accordingly.
        </p>
        <p>
          Backup copies are maintained on a rolling rotation schedule and are not used for day-to-day access.
          Backup snapshots are encrypted before storage. Where deleted data remains present in a backup set, it
          will be removed or rendered inaccessible in accordance with the applicable backup rotation and
          restoration procedures.
        </p>
      </Section>

      <Section title="6. Your Rights">
        <p>Depending on your jurisdiction, you may have the right to:</p>
        <ul>
          <li>Access the personal data we hold about you.</li>
          <li>Correct inaccurate data.</li>
          <li>Request deletion of your data (&ldquo;right to be forgotten&rdquo;).</li>
          <li>Export your data in a portable format.</li>
          <li>Object to or restrict certain processing activities.</li>
        </ul>
        <p>
          To exercise any of these rights, email{" "}
          <a href="mailto:privacy@buildledger.com">privacy@buildledger.com</a>.
        </p>
        <p>
          We aim to respond to download, rectification, and deletion requests within 30 days of receiving a
          complete request and any information reasonably required to verify your identity and process the request.
        </p>
      </Section>

      <Section title="7. Security">
        <p>
          We implement industry-standard security measures including HTTPS encryption in transit, hashed passwords
          (bcrypt), HTTP security headers, and rate limiting on authentication endpoints. No system is perfectly
          secure; we encourage you to use a strong, unique password.
        </p>
      </Section>

      <Section title="8. Children">
        <p>
          BuildLedger is not directed at children under 16. We do not knowingly collect data from minors.
          If you believe a minor has provided us with personal data, contact us immediately.
        </p>
      </Section>

      <Section title="9. Changes to This Policy">
        <p>
          We may update this policy from time to time. We will notify registered users by email and update the
          &ldquo;Last updated&rdquo; date above. Continued use of the platform after changes constitutes acceptance.
        </p>
      </Section>
    </article>
  );
}

// ── Shared sub-components ────────────────────────────────────────────────────

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

function SubSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="mb-4">
      <h3 className="mb-2 text-sm font-semibold text-zinc-200">{title}</h3>
      <div className="text-zinc-400">{children}</div>
    </div>
  );
}
