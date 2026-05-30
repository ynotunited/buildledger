import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Data & Compliance | BuildLedger",
  description: "BuildLedger's approach to data security, regulatory compliance, and your rights under applicable data protection laws.",
};

const LAST_UPDATED = "25 May 2026";

export default function DataCompliancePage() {
  return (
    <article>
      <LegalHeader
        badge="Compliance"
        title="Data & Compliance"
        updated={LAST_UPDATED}
        summary="BuildLedger is committed to responsible data handling and compliance with applicable data protection regulations. This page outlines our security practices, regulatory posture, and your rights."
      />

      <Section title="1. Our Compliance Commitment">
        <p>
          Webxpress Technologies operates BuildLedger in accordance with applicable data protection and privacy
          laws, including the <strong>Nigeria Data Protection Act 2023 (NDPA)</strong> and, where applicable,
          the <strong>EU General Data Protection Regulation (GDPR)</strong>.
        </p>
        <p>
          We treat data protection as a core business obligation, not an afterthought.
        </p>
      </Section>

      <Section title="2. Data Security Measures">
        <SubSection title="2.1 Encryption">
          <p>All data in transit is encrypted using TLS 1.2 or higher. Passwords are hashed using bcrypt with a minimum cost factor of 12. Sensitive configuration values are stored as environment variables, never in source code.</p>
        </SubSection>
        <SubSection title="2.2 Access Controls">
          <p>Access to production systems is restricted to authorised personnel only, using SSH key authentication. All API endpoints require authentication via Laravel Sanctum bearer tokens. Rate limiting is applied to authentication and payment endpoints to prevent brute-force attacks.</p>
        </SubSection>
        <SubSection title="2.3 HTTP Security Headers">
          <p>All responses include security headers: <code>X-Content-Type-Options</code>, <code>X-Frame-Options</code>, <code>X-XSS-Protection</code>, <code>Referrer-Policy</code>, <code>Permissions-Policy</code>, and <code>Strict-Transport-Security</code> (HSTS) on HTTPS connections.</p>
        </SubSection>
        <SubSection title="2.4 Infrastructure">
          <p>BuildLedger is hosted on dedicated VPS infrastructure. File uploads are stored on isolated storage with access-controlled signed URLs. Database backups are performed regularly and stored encrypted.</p>
        </SubSection>
      </Section>

      <Section title="3. Data Residency">
        <p>
          By default, BuildLedger stores data on servers located in Africa or Europe. We do not transfer personal
          data to jurisdictions without adequate data protection frameworks without appropriate safeguards
          (e.g. Standard Contractual Clauses).
        </p>
      </Section>

      <Section title="4. Nigeria Data Protection Act (NDPA) 2023">
        <p>As a Nigerian-operated platform, we comply with the NDPA 2023, which includes:</p>
        <ul>
          <li>Lawful basis for all data processing activities.</li>
          <li>Data subject rights: access, correction, deletion, portability, and objection.</li>
          <li>Appointment of a Data Protection Officer (DPO) where required.</li>
          <li>Data breach notification to the Nigeria Data Protection Commission (NDPC) within 72 hours of discovery.</li>
          <li>Data Protection Impact Assessments (DPIAs) for high-risk processing activities.</li>
        </ul>
      </Section>

      <Section title="5. GDPR (EU Users)">
        <p>For users in the European Economic Area, we comply with GDPR requirements including:</p>
        <ul>
          <li>Explicit lawful basis for processing (contract performance, legitimate interest, or consent).</li>
          <li>Right to erasure (&ldquo;right to be forgotten&rdquo;).</li>
          <li>Data portability in machine-readable format.</li>
          <li>72-hour breach notification to the relevant supervisory authority.</li>
        </ul>
      </Section>

      <Section title="6. Third-Party Sub-processors">
        <p>We use the following sub-processors to deliver the Service:</p>
        <table>
          <thead>
            <tr>
              <th>Sub-processor</th>
              <th>Purpose</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>
            <tr><td>Paystack</td><td>Payment processing</td><td>Nigeria / Global</td></tr>
            <tr><td>Flutterwave</td><td>Payment processing</td><td>Nigeria / Global</td></tr>
            <tr><td>Cloud hosting provider</td><td>Server infrastructure</td><td>Africa / EU</td></tr>
            <tr><td>Email delivery provider</td><td>Transactional email</td><td>EU / US</td></tr>
          </tbody>
        </table>
        <p>All sub-processors are bound by data processing agreements consistent with applicable law.</p>
      </Section>

      <Section title="7. Data Breach Response">
        <p>In the event of a data breach, we will:</p>
        <ul>
          <li>Contain and assess the breach within 24 hours of discovery.</li>
          <li>Notify affected users without undue delay if the breach poses a high risk to their rights.</li>
          <li>Report to the NDPC (and relevant EU supervisory authority where applicable) within 72 hours.</li>
          <li>Conduct a post-incident review and implement remediation measures.</li>
        </ul>
      </Section>

      <Section title="8. Audit & Certifications">
        <p>
          We conduct periodic internal security reviews. We are working towards formal compliance certifications
          and will update this page as certifications are obtained.
        </p>
      </Section>

      <Section title="9. Contact Our DPO">
        <p>
          For data protection enquiries, to exercise your rights, or to report a concern, contact our
          Data Protection Officer at{" "}
          <a href="mailto:dpo@buildledger.com">dpo@buildledger.com</a>.
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
      <div className="space-y-3 text-sm leading-7 text-zinc-400 [&_a]:text-emerald-400 [&_a]:underline-offset-2 [&_a]:hover:text-emerald-300 [&_code]:rounded [&_code]:bg-white/8 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-xs [&_code]:text-zinc-300 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-white/10 [&_td]:px-3 [&_td]:py-2 [&_th]:border [&_th]:border-white/10 [&_th]:bg-white/5 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wider [&_th]:text-zinc-400">
        {children}
      </div>
    </section>
  );
}

function SubSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="mb-4">
      <h3 className="mb-2 text-sm font-semibold text-zinc-200">{title}</h3>
      <div>{children}</div>
    </div>
  );
}
