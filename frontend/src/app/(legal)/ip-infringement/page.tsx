import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "IP Infringement Policy | BuildLedger",
  description: "How to report intellectual property infringement on the BuildLedger platform and how we respond.",
};

const LAST_UPDATED = "25 May 2026";

export default function IpInfringementPage() {
  return (
    <article>
      <LegalHeader
        badge="Legal"
        title="IP Infringement Policy"
        updated={LAST_UPDATED}
        summary="BuildLedger respects intellectual property rights and expects users to do the same. This policy explains how to report infringement and how we handle such reports."
      />

      <Section title="1. Our Commitment">
        <p>
          Webxpress Technologies respects the intellectual property rights of others and expects all users of
          BuildLedger to do the same. We will respond promptly to notices of alleged copyright, trademark, or
          other intellectual property infringement that comply with applicable law.
        </p>
      </Section>

      <Section title="2. Copyright Infringement (DMCA-Style Notice)">
        <p>
          If you believe that content accessible through BuildLedger infringes your copyright, you may submit
          a written notice to our designated agent. Your notice must include:
        </p>
        <ol>
          <li>
            <strong>Identification of the copyrighted work</strong> — a description of the work you claim has been infringed, or a representative list if multiple works are involved.
          </li>
          <li>
            <strong>Identification of the infringing material</strong> — sufficient information to locate the material on our platform (e.g. URL, account name, or description).
          </li>
          <li>
            <strong>Your contact information</strong> — your full name, mailing address, telephone number, and email address.
          </li>
          <li>
            <strong>Good faith statement</strong> — a statement that you have a good faith belief that the use of the material is not authorised by the copyright owner, its agent, or the law.
          </li>
          <li>
            <strong>Accuracy statement</strong> — a statement, under penalty of perjury, that the information in your notice is accurate and that you are the copyright owner or authorised to act on their behalf.
          </li>
          <li>
            <strong>Signature</strong> — your physical or electronic signature.
          </li>
        </ol>
        <p>
          Send your notice to:{" "}
          <a href="mailto:ip@buildledger.com">ip@buildledger.com</a>
        </p>
      </Section>

      <Section title="3. Trademark Infringement">
        <p>
          If you believe a user is using your trademark in a way that is likely to cause confusion, deception,
          or mistake, please contact us at <a href="mailto:ip@buildledger.com">ip@buildledger.com</a> with:
        </p>
        <ul>
          <li>Your trademark registration details (registration number, jurisdiction, and class).</li>
          <li>A description of how the mark is being used on our platform.</li>
          <li>Your contact information.</li>
        </ul>
      </Section>

      <Section title="4. Our Response Process">
        <p>Upon receiving a valid infringement notice, we will:</p>
        <ol>
          <li>Acknowledge receipt within <strong>2 business days</strong>.</li>
          <li>Review the notice and assess its validity.</li>
          <li>If valid, remove or disable access to the allegedly infringing content promptly.</li>
          <li>Notify the user who posted the content that it has been removed.</li>
          <li>Provide the user with an opportunity to submit a counter-notice if they believe the removal was in error.</li>
        </ol>
      </Section>

      <Section title="5. Counter-Notice">
        <p>
          If you believe your content was removed in error, you may submit a counter-notice to{" "}
          <a href="mailto:ip@buildledger.com">ip@buildledger.com</a> including:
        </p>
        <ul>
          <li>Identification of the removed content and its location before removal.</li>
          <li>A statement under penalty of perjury that you have a good faith belief the content was removed by mistake or misidentification.</li>
          <li>Your name, address, telephone number, and email address.</li>
          <li>A statement consenting to the jurisdiction of the courts of Nigeria.</li>
          <li>Your physical or electronic signature.</li>
        </ul>
        <p>
          If we receive a valid counter-notice, we may restore the content within 10–14 business days unless
          the original complainant files a court action.
        </p>
      </Section>

      <Section title="6. Repeat Infringers">
        <p>
          BuildLedger maintains a policy of terminating, in appropriate circumstances, the accounts of users
          who are repeat infringers of intellectual property rights.
        </p>
      </Section>

      <Section title="7. Misuse of This Process">
        <p>
          Submitting a false or misleading infringement notice is a serious matter. If you knowingly misrepresent
          that content is infringing, you may be liable for damages, including costs and legal fees, under
          applicable law.
        </p>
      </Section>

      <Section title="8. BuildLedger's Own Intellectual Property">
        <p>
          The BuildLedger name, logo, platform design, and codebase are the intellectual property of
          Webxpress Technologies. Unauthorised reproduction, distribution, or creation of derivative works
          is strictly prohibited and may result in legal action.
        </p>
      </Section>

      <Section title="9. Contact">
        <p>
          All IP-related notices and enquiries should be directed to:{" "}
          <a href="mailto:ip@buildledger.com">ip@buildledger.com</a>
        </p>
        <p>
          For general legal matters:{" "}
          <a href="mailto:legal@buildledger.com">legal@buildledger.com</a>
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
      <div className="space-y-3 text-sm leading-7 text-zinc-400 [&_a]:text-emerald-400 [&_a]:underline-offset-2 [&_a]:hover:text-emerald-300 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1">
        {children}
      </div>
    </section>
  );
}
