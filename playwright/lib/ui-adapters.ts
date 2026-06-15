// eslint-disable-next-line @typescript-eslint/no-var-requires
const { loadSelectorManifest, selectorsFor } = require('./ui-selector-manifest');

export type UiResource = Record<string, unknown> & {
  management_mode?: string;
  name?: string;
};

export type UiPlan = {
  resourceType: 'server_group' | 'server' | 'product_group';
  key: string;
  action: 'ensure';
  url: string;
  fields: Record<string, unknown>;
  selectors?: {
    page: string;
    form: string;
    submit: string;
    fields: Record<string, string>;
  };
};

type AdapterOptions = {
  adminUrl: string;
  liveApply?: boolean;
};

abstract class BaseUiAdapter {
  protected readonly adminUrl: string;
  protected readonly liveApply: boolean;

  protected constructor(options: AdapterOptions) {
    this.adminUrl = options.adminUrl.replace(/\/+$/, '');
    this.liveApply = options.liveApply ?? process.env.WHMCS_UI_LIVE_APPLY === '1';
  }

  async apply(resource: UiResource, key: string): Promise<UiPlan> {
    const plan = this.plan(resource, key);
    if (!this.liveApply) {
      return plan;
    }

    const manifest = loadSelectorManifest();
    return {
      ...plan,
      selectors: selectorsFor(manifest, plan.resourceType),
    };
  }

  protected url(path: string): string {
    return `${this.adminUrl}/${path.replace(/^\/+/, '')}`;
  }

  abstract plan(resource: UiResource, key: string): UiPlan;
}

export class ServerGroupUiAdapter extends BaseUiAdapter {
  plan(resource: UiResource, key: string): UiPlan {
    return {
      resourceType: 'server_group',
      key,
      action: 'ensure',
      url: this.url('configservers.php'),
      fields: pick(resource, ['name', 'fill_strategy']),
    };
  }
}

export class ServerUiAdapter extends BaseUiAdapter {
  plan(resource: UiResource, key: string): UiPlan {
    return {
      resourceType: 'server',
      key,
      action: 'ensure',
      url: this.url('configservers.php'),
      fields: pick(resource, [
        'group_key',
        'module',
        'hostname',
        'ipaddress',
        'username',
        'secure',
        'port',
        'credentials_ref',
      ]),
    };
  }
}

export class ProductGroupUiAdapter extends BaseUiAdapter {
  plan(resource: UiResource, key: string): UiPlan {
    return {
      resourceType: 'product_group',
      key,
      action: 'ensure',
      url: this.url('configproducts.php'),
      fields: pick(resource, [
        'name',
        'slug',
        'headline',
        'orderform_template',
        'allowed_gateways',
      ]),
    };
  }
}

export function buildUiPlans(
  state: {
    server_groups?: Record<string, UiResource>;
    servers?: Record<string, UiResource>;
    product_groups?: Record<string, UiResource>;
  },
  adminUrl: string,
): UiPlan[] {
  const plans: UiPlan[] = [];
  const serverGroupAdapter = new ServerGroupUiAdapter({ adminUrl });
  const serverAdapter = new ServerUiAdapter({ adminUrl });
  const productGroupAdapter = new ProductGroupUiAdapter({ adminUrl });

  for (const [key, resource] of Object.entries(state.server_groups ?? {})) {
    if (resource.management_mode === 'ui') {
      plans.push(serverGroupAdapter.plan(resource, key));
    }
  }

  for (const [key, resource] of Object.entries(state.servers ?? {})) {
    if (resource.management_mode === 'ui') {
      plans.push(serverAdapter.plan(resource, key));
    }
  }

  for (const [key, resource] of Object.entries(state.product_groups ?? {})) {
    if (resource.management_mode === 'ui') {
      plans.push(productGroupAdapter.plan(resource, key));
    }
  }

  return plans;
}

function pick(resource: UiResource, fields: string[]): Record<string, unknown> {
  const picked: Record<string, unknown> = {};

  for (const field of fields) {
    if (resource[field] !== undefined) {
      picked[field] = resource[field];
    }
  }

  return picked;
}
