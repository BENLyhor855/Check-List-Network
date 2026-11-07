# Check-List-Network

A vendor-neutral working checklist for planning, validating, and handing over a small office or branch network. Use it for a new installation or a significant change to an existing network. This first version assumes a managed router/firewall, switches, wired clients, and optionally Wi-Fi; adapt or mark items not applicable for your environment.

## Start here

1. Agree on the site, scope, change window, owner, and acceptance criteria with the network owner.
2. Copy [the site record](../templates/site-record.md) into a **private** location and fill in the inventory and test results as you work. This repository is public: do not commit credentials, configuration backups, internal addresses, diagrams, or other sensitive site data.
3. Work through the [network checklist](../checklists/network-deployment.md) in order. Record evidence and the owner for failures or exceptions in the site record. An unchecked box means work remains; record “not applicable” with a reason rather than checking an unperformed test.
4. Review the acceptance criteria and open exceptions with the owner before handover. Use the [task breakdown](task-breakdown.md) to plan follow-up improvements to this starter.

## Scope

The checklist covers discovery, physical installation, addressing and core services, segmentation and access, validation, recovery, and handover. It does not prescribe a vendor, IP plan, topology, firewall rules, or performance thresholds. Agree those values for each site before testing. Cloud networking, advanced routing, regulatory certification, and automated configuration are outside this first version.

## Repository layout

```text
README.md                         Purpose and workflow
checklists/network-deployment.md  Practical deployment and verification checklist
templates/site-record.md          Reusable private site and test record
docs/task-breakdown.md            Prioritized next steps and acceptance criteria
```

Changes to the checklist should describe the intended outcome and how to verify it. Avoid including real site data in examples or contributions.
